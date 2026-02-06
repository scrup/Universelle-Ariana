<?php
// src/Entity/Categorie.php

namespace App\Entity;

use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[ORM\Table(name: 'categorie')]
#[ORM\UniqueConstraint(name: 'uniq_categorie_name', columns: ['name'])]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $name = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $slug = null;

    /** @var Collection<int, CaseSocial> */
    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: CaseSocial::class)]
    private Collection $cases;

    public function __construct()
    {
        $this->cases = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): self { $this->slug = $slug; return $this; }

    /** @return Collection<int, CaseSocial> */
    public function getCases(): Collection { return $this->cases; }

    public function addCase(CaseSocial $case): self
    {
        if (!$this->cases->contains($case)) {
            $this->cases->add($case);
            $case->setCategorie($this);
        }
        return $this;
    }

    public function removeCase(CaseSocial $case): self
    {
        if ($this->cases->removeElement($case)) {
            if ($case->getCategorie() === $this) {
                $case->setCategorie(null);
            }
        }
        return $this;
    }
}
