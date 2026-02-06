<?php
// src/Entity/CasePhoto.php

namespace App\Entity;

use App\Repository\CasePhotoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CasePhotoRepository::class)]
#[ORM\Table(name: 'case_photo')]
class CasePhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Store relative path, e.g. "cases/12/photo1.jpg"
    #[ORM\Column(length: 255)]
    private string $filePath = '';

    #[ORM\Column(length: 140, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(name: 'uploaded_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $uploadedAt;

    #[ORM\ManyToOne(inversedBy: 'photos')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CaseSocial $caseSocial = null;

    public function __construct()
    {
        $this->uploadedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFilePath(): string { return $this->filePath; }
    public function setFilePath(string $filePath): self { $this->filePath = $filePath; return $this; }

    public function getCaption(): ?string { return $this->caption; }
    public function setCaption(?string $caption): self { $this->caption = $caption; return $this; }

    public function getUploadedAt(): \DateTimeImmutable { return $this->uploadedAt; }
    public function setUploadedAt(\DateTimeImmutable $uploadedAt): self { $this->uploadedAt = $uploadedAt; return $this; }

    public function getCaseSocial(): ?CaseSocial { return $this->caseSocial; }
    public function setCaseSocial(?CaseSocial $caseSocial): self { $this->caseSocial = $caseSocial; return $this; }
}
