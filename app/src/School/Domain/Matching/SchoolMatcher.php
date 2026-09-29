<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

use App\School\Domain\School;
use App\School\Domain\SchoolRepository;
use App\School\Domain\SchoolType;

/**
 * Cascade: exact key lookup → informativeness check → token-based fuzzy scoring → decision.
 */
final class SchoolMatcher
{
    public const array GENERIC_TOKENS = ['lo', 'technikum', 'zs'];

    private const float GENERIC_WEIGHT = 0.3;
    private const float DEFAULT_WEIGHT = 1.0;
    private const float TYPE_MISMATCH_PENALTY = 0.5;
    private const float CITY_MISMATCH_PENALTY = 0.7;
    private const int MAX_AMBIGUOUS = 5;
    private const int MAX_SUGGESTIONS = 3;

    public function __construct(
        private readonly SchoolRepository $repository,
        private readonly NameNormalizer $normalizer,
        private readonly float $matchThreshold = 0.85,
        private readonly float $suggestionThreshold = 0.6,
        private readonly float $ambiguityMargin = 0.1,
    ) {
    }

    public function match(string $rawName, ?string $city): MatchResult
    {
        $normalized = $this->normalizer->normalize($rawName);
        if ($normalized->isEmpty()) {
            return MatchResult::notFound($normalized, null);
        }

        [$query, $cityHint] = $this->extractCity($normalized, $city);
        $typeHint = $this->detectType($query);

        $exact = $this->exactMatch($query, $cityHint);
        if (null !== $exact) {
            return new MatchResult(
                \count($exact) > 1 ? MatchStatus::Ambiguous : MatchStatus::Matched,
                $normalized,
                $exact,
                $cityHint,
            );
        }

        if (!$this->isInformative($query)) {
            return MatchResult::notFound($normalized, $cityHint);
        }

        return $this->decide($normalized, $this->fuzzyCandidates($query, $cityHint, $typeHint), $cityHint);
    }

    private function extractCity(NormalizedName $query, ?string $city): array
    {
        $cityHint = null;
        $knownCities = [];

        if (null !== $city && '' !== trim($city)) {
            $cityHint = $this->normalizer->normalize($city)->value();
            $knownCities = [$cityHint];
        } else {
            foreach ($this->repository->findKnownCities() as $knownCity) {
                $knownCities[] = $this->normalizer->normalize($knownCity)->value();
            }
        }

        foreach ($query->tokens() as $token) {
            foreach ($knownCities as $knownCity) {
                if ($this->isSameCity($token, $knownCity)) {
                    return [$query->withoutTokens([$token]), $cityHint ?? $knownCity];
                }
            }
        }

        return [$query, '' === $cityHint ? null : $cityHint];
    }

    private function isSameCity(string $token, string $city): bool
    {
        return $token === $city
            || (\strlen($token) >= 5 && levenshtein($token, $city) <= 1);
    }

    private function detectType(NormalizedName $query): ?SchoolType
    {
        foreach ($query->tokens() as $token) {
            $type = SchoolType::fromNormalizedToken($token);
            if (null !== $type) {
                return $type;
            }
        }

        return null;
    }

    private function exactMatch(NormalizedName $query, ?string $cityHint): ?array
    {
        $key = $query->key();
        if ('' === $key) {
            return null;
        }

        $schools = array_filter(
            $this->repository->findByAliasKey($key),
            fn (School $school): bool => $this->cityMatches($school, $cityHint),
        );
        if ([] === $schools) {
            return null;
        }

        $candidates = [];
        foreach ($schools as $school) {
            $candidates[] = new MatchCandidate($school, 1.0, $this->aliasNameForKey($school, $key));
        }

        return $candidates;
    }

    private function aliasNameForKey(School $school, string $key): string
    {
        foreach ($school->aliases() as $alias) {
            if ($alias->normalizedName() === $key) {
                return $alias->name();
            }
        }

        return $school->officialName();
    }

    private function isInformative(NormalizedName $query): bool
    {
        $hasType = false;
        foreach ($query->tokens() as $token) {
            if (!ctype_digit($token) && !\in_array($token, self::GENERIC_TOKENS, true)) {
                return true;
            }
            $hasType = $hasType || null !== SchoolType::fromNormalizedToken($token);
        }

        return $hasType && [] !== $query->numbers();
    }

    private function fuzzyCandidates(NormalizedName $query, ?string $cityHint, ?SchoolType $typeHint): array
    {
        $queryTokens = $query->keyTokens();
        $candidates = [];

        foreach ($this->repository->findAllWithAliases() as $school) {
            $best = null;
            foreach ($school->aliases() as $alias) {
                $score = $this->aliasScore($queryTokens, explode(' ', $alias->normalizedName()));
                if ($score > 0.0 && (null === $best || $score > $best[0])) {
                    $best = [$score, $alias->name()];
                }
            }
            if (null === $best) {
                continue;
            }

            $score = $best[0];
            if (null !== $typeHint && $typeHint !== $school->type()) {
                $score *= self::TYPE_MISMATCH_PENALTY;
            }
            if (!$this->cityMatches($school, $cityHint)) {
                $score *= self::CITY_MISMATCH_PENALTY;
            }

            $candidates[] = new MatchCandidate($school, $score, $best[1]);
        }

        usort($candidates, static fn (MatchCandidate $a, MatchCandidate $b): int => $b->score <=> $a->score);

        return $candidates;
    }

    private function aliasScore(array $queryTokens, array $keyTokens): float
    {
        $queryNumbers = array_filter($queryTokens, ctype_digit(...));
        $keyNumbers = array_filter($keyTokens, ctype_digit(...));
        if ([] !== $queryNumbers && [] !== $keyNumbers && [] === array_intersect($queryNumbers, $keyNumbers)) {
            return 0.0; // "II LO" must never match "III LO"
        }

        $matched = 0.0;
        $unused = $keyTokens;
        foreach ($queryTokens as $queryToken) {
            $bestIndex = null;
            $bestSim = 0.0;
            foreach ($unused as $index => $keyToken) {
                $sim = $this->tokenSimilarity($queryToken, $keyToken);
                if ($sim > $bestSim) {
                    $bestSim = $sim;
                    $bestIndex = $index;
                }
            }
            if (null !== $bestIndex) {
                unset($unused[$bestIndex]);
                $matched += $this->weight($queryToken) * $bestSim;
            }
        }

        $coverageQuery = $matched / $this->totalWeight($queryTokens);
        $coverageKey = $matched / $this->totalWeight($keyTokens);

        return 0.7 * $coverageQuery + 0.3 * $coverageKey;
    }

    private function tokenSimilarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }
        if (ctype_digit($a) || ctype_digit($b) || \strlen($a) < 4 || \strlen($b) < 4) {
            return 0.0;
        }

        $distance = levenshtein($a, $b);
        $limit = min(\strlen($a), \strlen($b)) < 7 ? 1 : 2;
        if ($distance > $limit) {
            return 0.0;
        }

        return 1 - $distance / max(\strlen($a), \strlen($b));
    }

    private function weight(string $token): float
    {
        return \in_array($token, self::GENERIC_TOKENS, true) ? self::GENERIC_WEIGHT : self::DEFAULT_WEIGHT;
    }

    private function totalWeight(array $tokens): float
    {
        return array_sum(array_map($this->weight(...), $tokens));
    }

    private function cityMatches(School $school, ?string $cityHint): bool
    {
        return null === $cityHint || $this->normalizer->normalize($school->city())->value() === $cityHint;
    }

    private function decide(NormalizedName $query, array $candidates, ?string $cityHint): MatchResult
    {
        $top = $candidates[0] ?? null;
        if (null === $top || $top->score < $this->suggestionThreshold) {
            return MatchResult::notFound($query, $cityHint);
        }

        if ($top->score < $this->matchThreshold) {
            $suggestions = array_filter($candidates, fn (MatchCandidate $c): bool => $c->score >= $this->suggestionThreshold);

            return new MatchResult(MatchStatus::Suggestions, $query, \array_slice($suggestions, 0, self::MAX_SUGGESTIONS), $cityHint);
        }

        $second = $candidates[1] ?? null;
        if (null === $second || $top->score - $second->score >= $this->ambiguityMargin) {
            return new MatchResult(MatchStatus::Matched, $query, [$top], $cityHint);
        }

        $ambiguous = array_filter($candidates, fn (MatchCandidate $c): bool => $c->score >= $this->matchThreshold);

        return new MatchResult(MatchStatus::Ambiguous, $query, \array_slice($ambiguous, 0, self::MAX_AMBIGUOUS), $cityHint);
    }
}
