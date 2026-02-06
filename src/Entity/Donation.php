<?php
// src/Entity/Donation.php

namespace App\Entity;

use App\Repository\DonationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DonationRepository::class)]
#[ORM\Table(name: 'donation')]
#[ORM\Index(name: 'idx_donation_donated_at', columns: ['donated_at'])]
class Donation
{
    public const STATUS_DECLARED  = 'DECLARED';   // user says they donated (no proof)
    public const STATUS_CONFIRMED = 'CONFIRMED';  // if later you can confirm via receipt/admin
    public const STATUS_CANCELED  = 'CANCELED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Use string to avoid float errors (money)
    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    private string $amount = '0.000';

    #[ORM\Column(name: 'donated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $donatedAt;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DECLARED;

    // Optional: Cha9a9a reference / receipt code if you have it
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $reference = null;

    // Optional: small note from donor
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?User $donor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CaseSocial $caseSocial = null;

    public function __construct()
    {
        $this->donatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self
    {
        // minimal guard (no negative)
        if (bccomp($amount, '0', 3) === -1) {
            $amount = '0.000';
        }
        $this->amount = $amount;
        return $this;
    }

    public function getDonatedAt(): \DateTimeImmutable { return $this->donatedAt; }
    public function setDonatedAt(\DateTimeImmutable $donatedAt): self { $this->donatedAt = $donatedAt; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $reference): self { $this->reference = $reference; return $this; }

    public function getNote(): ?string { return $this->note; }
    public function setNote(?string $note): self { $this->note = $note; return $this; }

    public function getDonor(): ?User { return $this->donor; }
    public function setDonor(?User $donor): self { $this->donor = $donor; return $this; }

    public function getCaseSocial(): ?CaseSocial { return $this->caseSocial; }
    public function setCaseSocial(?CaseSocial $caseSocial): self { $this->caseSocial = $caseSocial; return $this; }
}
