<?php

declare(strict_types=1);

namespace App\School\Application\MatchSchool;

use App\School\Domain\Matching\MatchCandidate;
use App\School\Domain\Matching\MatchResult;

final readonly class MatchSchoolResponse
{
    public function __construct(
        public string $query,
        public string $normalized,
        public ?string $cityHint,
        public string $status,
        public array $candidates,
    ) {
    }

    public static function fromResult(string $rawQuery, MatchResult $result): self
    {
        return new self(
            $rawQuery,
            $result->query->value(),
            $result->cityHint,
            $result->status->value,
            array_map(static fn (MatchCandidate $candidate): array => [
                'id' => $candidate->school->id(),
                'name' => $candidate->school->officialName(),
                'city' => $candidate->school->city(),
                'type' => $candidate->school->type()->value,
                'score' => round($candidate->score, 2),
                'matchedAlias' => $candidate->matchedAlias,
            ], $result->candidates),
        );
    }

    public function toArray(): array
    {
        return [
            'query' => $this->query,
            'normalized' => $this->normalized,
            'cityHint' => $this->cityHint,
            'status' => $this->status,
            'candidates' => $this->candidates,
        ];
    }
}
