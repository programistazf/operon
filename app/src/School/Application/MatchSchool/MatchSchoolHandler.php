<?php

declare(strict_types=1);

namespace App\School\Application\MatchSchool;

use App\School\Domain\Matching\SchoolMatcher;

final class MatchSchoolHandler
{
    public function __construct(private readonly SchoolMatcher $matcher)
    {
    }

    public function __invoke(MatchSchoolQuery $query): MatchSchoolResponse
    {
        return MatchSchoolResponse::fromResult($query->name, $this->matcher->match($query->name, $query->city));
    }
}
