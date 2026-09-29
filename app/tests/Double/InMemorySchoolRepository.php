<?php

declare(strict_types=1);

namespace App\Tests\Double;

use App\School\Domain\School;
use App\School\Domain\SchoolRepository;

final class InMemorySchoolRepository implements SchoolRepository
{
    private array $schools = [];

    public function add(School $school): void
    {
        $this->schools[] = $school;
    }

    public function findByAliasKey(string $key): array
    {
        return array_values(array_filter(
            $this->schools,
            static function (School $school) use ($key): bool {
                foreach ($school->aliases() as $alias) {
                    if ($alias->normalizedName() === $key) {
                        return true;
                    }
                }

                return false;
            },
        ));
    }

    public function findAllWithAliases(): array
    {
        return $this->schools;
    }

    public function findKnownCities(): array
    {
        return array_values(array_unique(array_map(static fn (School $s): string => $s->city(), $this->schools)));
    }
}
