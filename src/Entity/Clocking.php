<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClockingRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClockingRepository::class)]
class Clocking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'clockings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $clockingUser = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $date = null;

    #[ORM\OneToMany(mappedBy: 'clocking', targetEntity: ClockingEntry::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClockingUser(): ?User
    {
        return $this->clockingUser;
    }

    public function setClockingUser(?User $clockingUser): static
    {
        $this->clockingUser = $clockingUser;

        return $this;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return Collection<int, ClockingEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(ClockingEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries[] = $entry;
            $entry->setClocking($this);
        }

        return $this;
    }

    public function removeEntry(ClockingEntry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            if ($entry->getClocking() === $this) {
                $entry->setClocking(null);
            }
        }

        return $this;
    }
}
