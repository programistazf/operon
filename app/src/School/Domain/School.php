<?php

declare(strict_types=1);

namespace App\School\Domain;

use App\School\Domain\Matching\NameNormalizer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'school')]
final class School
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: SchoolAlias::class, mappedBy: 'school', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $aliases;

    private function __construct(
        #[ORM\Column(length: 255)]
        private string $officialName,
        #[ORM\Column(length: 100)]
        private string $city,
        #[ORM\Column(length: 20, enumType: SchoolType::class)]
        private SchoolType $type,
    ) {
        $this->aliases = new ArrayCollection();
    }

    public static function register(string $officialName, string $city, SchoolType $type, NameNormalizer $normalizer): self
    {
        $school = new self($officialName, $city, $type);
        $school->attachAlias($officialName, AliasKind::Official, $normalizer);

        return $school;
    }

    /**
     * Idempotent: an alias whose key already exists for this school is silently skipped.
     */
    public function addAlias(string $name, NameNormalizer $normalizer): void
    {
        $this->attachAlias($name, AliasKind::Alias, $normalizer);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function officialName(): string
    {
        return $this->officialName;
    }

    public function city(): string
    {
        return $this->city;
    }

    public function type(): SchoolType
    {
        return $this->type;
    }

    public function aliases(): array
    {
        return array_values($this->aliases->toArray());
    }

    private function attachAlias(string $name, AliasKind $kind, NameNormalizer $normalizer): void
    {
        $key = $normalizer->normalize($name)->key();
        if ('' === $key) {
            return;
        }

        foreach ($this->aliases as $alias) {
            if ($alias->normalizedName() === $key) {
                return;
            }
        }

        $this->aliases->add(new SchoolAlias($this, $name, $key, $kind));
    }
}
