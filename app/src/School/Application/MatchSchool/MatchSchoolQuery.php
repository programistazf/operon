<?php

declare(strict_types=1);

namespace App\School\Application\MatchSchool;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MatchSchoolQuery
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 200)]
        public string $name = '',
        #[Assert\Length(max: 100)]
        public ?string $city = null,
    ) {
    }
}
