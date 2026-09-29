<?php

declare(strict_types=1);

namespace App\School\Domain;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'school_alias')]
#[ORM\Index(name: 'idx_school_alias_normalized_name', columns: ['normalized_name'])]
#[ORM\UniqueConstraint(name: 'uniq_school_alias_school_normalized_name', columns: ['school_id', 'normalized_name'])]
final class SchoolAlias
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @internal created only by the School aggregate
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: School::class, inversedBy: 'aliases')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private School $school,
        #[ORM\Column(length: 255)]
        private string $name,
        #[ORM\Column(length: 255)]
        private string $normalizedName,
        #[ORM\Column(length: 20, enumType: AliasKind::class)]
        private AliasKind $kind,
    ) {
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function school(): School
    {
        return $this->school;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function normalizedName(): string
    {
        return $this->normalizedName;
    }

    public function kind(): AliasKind
    {
        return $this->kind;
    }
}
