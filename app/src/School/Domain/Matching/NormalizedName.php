<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

final readonly class NormalizedName
{
    private array $tokens;

    public function __construct(array $tokens)
    {
        $this->tokens = array_values(array_filter($tokens, static fn (string $t): bool => '' !== $t));
    }

    public function value(): string
    {
        return implode(' ', $this->tokens);
    }

    public function tokens(): array
    {
        return $this->tokens;
    }

    public function keyTokens(): array
    {
        $unique = array_values(array_unique($this->tokens));
        sort($unique, \SORT_STRING);

        return $unique;
    }

    /** Order-independent lookup key stored in school_alias.normalized_name. */
    public function key(): string
    {
        return implode(' ', $this->keyTokens());
    }

    public function numbers(): array
    {
        return array_values(array_unique(array_filter($this->tokens, static fn (string $t): bool => ctype_digit($t))));
    }

    public function withoutTokens(array $tokens): self
    {
        return new self(array_values(array_filter($this->tokens, static fn (string $t): bool => !\in_array($t, $tokens, true))));
    }

    public function isEmpty(): bool
    {
        return [] === $this->tokens;
    }
}
