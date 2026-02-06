<?php
// src/Entity/CaseSocial.php

namespace App\Entity;

use App\Repository\CaseSocialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaseSocialRepository::class)]
#[ORM\Table(name: 'case_social')]
#[ORM\Index(name: 'idx_case_created_at', columns: ['created_at'])]
#[ORM\Index(name: 'idx_case_urgent', columns: ['is_urgent'])]
class CaseSocial
{
    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_REJECTED  = 'REJECTED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(length: 255)]
    private string $cha9a9aLink = '';

    #[ORM\Column(name: 'is_urgent', type: 'boolean')]
    private bool $isUrgent = false;

    #[ORM\Column(name: 'views_count')]
    private int $viewsCount = 0;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(inversedBy: 'cases')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne(inversedBy: 'publishedCases')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $publisher = null;

    /** @var Collection<int, CasePhoto> */
    #[ORM\OneToMany(mappedBy: 'caseSocial', targetEntity: CasePhoto::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $photos;

    /** @var Collection<int, Donation> */
    #[ORM\OneToMany(mappedBy: 'caseSocial', targetEntity: Donation::class, cascade: ['remove'])]
    private Collection $donations;

    public function __construct()
    {
        $this->createdAt  = new \DateTimeImmutable();
        $this->photos     = new ArrayCollection();
        $this->donations  = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }

    public function getCha9a9aLink(): string { return $this->cha9a9aLink; }
    public function setCha9a9aLink(string $link): self { $this->cha9a9aLink = $link; return $this; }

    public function isUrgent(): bool { return $this->isUrgent; }
    public function setIsUrgent(bool $isUrgent): self { $this->isUrgent = $isUrgent; return $this; }

    public function getViewsCount(): int { return $this->viewsCount; }
    public function setViewsCount(int $viewsCount): self { $this->viewsCount = max(0, $viewsCount); return $this; }
    public function incrementViews(int $by = 1): self { $this->viewsCount += max(0, $by); return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): self { $this->categorie = $categorie; return $this; }

    public function getPublisher(): ?User { return $this->publisher; }
    public function setPublisher(?User $publisher): self { $this->publisher = $publisher; return $this; }

    /** @return Collection<int, CasePhoto> */
    public function getPhotos(): Collection { return $this->photos; }

    public function addPhoto(CasePhoto $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setCaseSocial($this);
        }
        return $this;
    }

    public function removePhoto(CasePhoto $photo): self
    {
        if ($this->photos->removeElement($photo)) {
            if ($photo->getCaseSocial() === $this) {
                $photo->setCaseSocial(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Donation> */
    public function getDonations(): Collection { return $this->donations; }

    public function addDonation(Donation $donation): self
    {
        if (!$this->donations->contains($donation)) {
            $this->donations->add($donation);
            $donation->setCaseSocial($this);
        }
        return $this;
    }

    public function removeDonation(Donation $donation): self
    {
        if ($this->donations->removeElement($donation)) {
            if ($donation->getCaseSocial() === $this) {
                $donation->setCaseSocial(null);
            }
        }
        return $this;
    }
}
