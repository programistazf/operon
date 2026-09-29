<?php

declare(strict_types=1);

namespace App\School\Infrastructure\Fixtures;

use App\School\Domain\Matching\NameNormalizer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class SchoolFixtures extends Fixture
{
    public function __construct(private readonly NameNormalizer $normalizer)
    {
    }

    public function load(ObjectManager $manager): void
    {
        foreach (SchoolDataset::schools($this->normalizer) as $school) {
            $manager->persist($school);
        }

        $manager->flush();
    }
}
