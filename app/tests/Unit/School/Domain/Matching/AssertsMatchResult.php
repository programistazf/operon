<?php

declare(strict_types=1);

namespace App\Tests\Unit\School\Domain\Matching;

use App\School\Domain\Matching\MatchCandidate;
use App\School\Domain\Matching\MatchResult;

trait AssertsMatchResult
{
    private function top(MatchResult $result): MatchCandidate
    {
        self::assertNotEmpty($result->candidates, $this->describe($result));

        return $result->candidates[0];
    }

    private function describe(MatchResult $result): string
    {
        return \sprintf('%s [%s]: %s', $result->status->value, $result->query->value(), implode(', ', array_map(
            static fn (MatchCandidate $c): string => \sprintf('%s (%s)=%.3f', $c->school->officialName(), $c->school->city(), $c->score),
            $result->candidates,
        )));
    }
}
