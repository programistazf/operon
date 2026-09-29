<?php

declare(strict_types=1);

namespace App\School\Domain\Matching;

use App\School\Domain\School;

final readonly class MatchCandidate
{
    public function __construct(
        public School $school,
        public float $score,
        public string $matchedAlias,
    ) {
    }
}
