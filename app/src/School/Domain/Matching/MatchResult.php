<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

final readonly class MatchResult
{
    public function __construct(
        public MatchStatus $status,
        public NormalizedName $query,
        public array $candidates,
        public ?string $cityHint,
    ) {
    }

    public static function notFound(NormalizedName $query, ?string $cityHint): self
    {
        return new self(MatchStatus::NotFound, $query, [], $cityHint);
    }
}
