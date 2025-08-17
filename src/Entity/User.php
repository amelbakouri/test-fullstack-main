<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['matricule'], message: 'There is already an account with this matricule')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER  = 'ROLE_USER';   // Collaborateur
    public const ROLE_PM    = 'ROLE_PM';     // Chef de projet
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $matricule = null; 


    #[ORM\Column(type: 'json', nullable: false, options: ['default' => '[]'])]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: Clocking::class, mappedBy: 'clockingUser', orphanRemoval: true)]
    private Collection $clockings;

    public function __construct()
    {
        $this->clockings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getMatricule(): ?string
    {
        return $this->matricule;
    }
    public function setMatricule(?string $matricule): void
    {
        $this->matricule = $matricule;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->matricule;
    }


    public function getRoles(): array
    {
        $roles = $this->roles ?? [];
        if ($roles === []) {
            $roles = [self::ROLE_USER];
        }
        return array_values(array_unique($roles));
    }

    public function setRoles(array $roles): static
    {
        $roles = array_values(array_unique(array_filter($roles)));
        if ($roles === []) {
            $roles = [self::ROLE_USER];
        }
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // $this->plainPassword = null;
    }



    /** @return Collection<int, Clocking> */
    public function getClockings(): Collection
    {
        return $this->clockings;
    }

    public function addClocking(Clocking $clocking): static
    {
        if (!$this->clockings->contains($clocking)) {
            $this->clockings->add($clocking);
            $clocking->setClockingUser($this);
        }
        return $this;
    }

    public function removeClocking(Clocking $clocking): static
    {
        if ($this->clockings->removeElement($clocking) && $clocking->getClockingUser() === $this) {
            $clocking->setClockingUser(null);
        }
        return $this;
    }
    public function __toString(): string
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? '')) ?: (string) $this->matricule;
    }
}
