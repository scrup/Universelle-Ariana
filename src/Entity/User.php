<?php
// src/Entity/User.php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'uniq_user_email', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_DONATEUR = 'ROLE_DONATEUR';
    public const ROLE_ASSOC    = 'ROLE_ASSOC';
    public const ROLE_ADMIN    = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email = '';

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $fullName = null;

    /** @var Collection<int, CaseSocial> */
    #[ORM\OneToMany(mappedBy: 'publisher', targetEntity: CaseSocial::class)]
    private Collection $publishedCases;

    /** @var Collection<int, Evenement> */
    #[ORM\OneToMany(mappedBy: 'createdBy', targetEntity: Evenement::class)]
    private Collection $eventsCreated;

    /** @var Collection<int, Donation> */
    #[ORM\OneToMany(mappedBy: 'donor', targetEntity: Donation::class, cascade: ['remove'])]
    private Collection $donations;

    public function __construct()
    {
        $this->publishedCases = new ArrayCollection();
        $this->eventsCreated  = new ArrayCollection();
        $this->donations      = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = mb_strtolower($email); return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_DONATEUR; // default minimal role
        return array_values(array_unique($roles));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getFullName(): ?string { return $this->fullName; }
    public function setFullName(?string $fullName): self { $this->fullName = $fullName; return $this; }

    /** @return Collection<int, CaseSocial> */
    public function getPublishedCases(): Collection { return $this->publishedCases; }

    public function addPublishedCase(CaseSocial $case): self
    {
        if (!$this->publishedCases->contains($case)) {
            $this->publishedCases->add($case);
            $case->setPublisher($this);
        }
        return $this;
    }

    public function removePublishedCase(CaseSocial $case): self
    {
        if ($this->publishedCases->removeElement($case)) {
            if ($case->getPublisher() === $this) {
                $case->setPublisher(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, Evenement> */
    public function getEventsCreated(): Collection { return $this->eventsCreated; }

    public function addEventCreated(Evenement $event): self
    {
        if (!$this->eventsCreated->contains($event)) {
            $this->eventsCreated->add($event);
            $event->setCreatedBy($this);
        }
        return $this;
    }

    public function removeEventCreated(Evenement $event): self
    {
        if ($this->eventsCreated->removeElement($event)) {
            if ($event->getCreatedBy() === $this) {
                $event->setCreatedBy(null);
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
            $donation->setDonor($this);
        }
        return $this;
    }

    public function removeDonation(Donation $donation): self
    {
        if ($this->donations->removeElement($donation)) {
            if ($donation->getDonor() === $this) {
                $donation->setDonor(null);
            }
        }
        return $this;
    }
}
