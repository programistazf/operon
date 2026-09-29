<?php

declare(strict_types=1);

namespace App\School\Infrastructure;

use App\School\Domain\School;
use App\School\Domain\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSchoolRepository implements SchoolRepository
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function findByAliasKey(string $key): array
    {
        return $this->em->createQuery(
            'SELECT s FROM '.School::class.' s
             WHERE s.id IN (
                 SELECT IDENTITY(a.school) FROM App\School\Domain\SchoolAlias a WHERE a.normalizedName = :key
             )
             ORDER BY s.id',
        )
            ->setParameter('key', $key)
            ->getResult();
    }

    public function findAllWithAliases(): array
    {
        return $this->em->createQuery('SELECT s, a FROM '.School::class.' s LEFT JOIN s.aliases a ORDER BY s.id, a.id')
            ->getResult();
    }

    public function findKnownCities(): array
    {
        return $this->em->createQuery('SELECT DISTINCT s.city FROM '.School::class.' s ORDER BY s.city')
            ->getSingleColumnResult();
    }
}
