<?php

declare(strict_types=1);

namespace App\School\Domain;

interface SchoolRepository
{
    public function findByAliasKey(string $key): array;

    public function findAllWithAliases(): array;

    public function findKnownCities(): array;
}
