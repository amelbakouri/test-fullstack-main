<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectRepository;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int               $id        = null;
    #[ORM\Column(length: 255)]
    private ?string            $name      = null;
    #[ORM\Column(length: 255)]
    private ?string            $address   = null;
    #[ORM\OneToMany(targetEntity: Clocking::class, mappedBy: 'clockingProject', orphanRemoval: true)]
    private Collection         $clockings;
    #[Assert\GreaterThan(propertyPath: 'dateStart')]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $dateEnd   = null;
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?DateTimeInterface $dateStart = null;
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: ClockingEntry::class, orphanRemoval: true)]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    /** @return Collection<int, ClockingEntry> */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(ClockingEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setProject($this);
        }
        return $this;
    }

    public function removeEntry(ClockingEntry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            if ($entry->getProject() === $this) {
                $entry->setProject(null);
            }
        }
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return Collection<int, Clocking>
     */
    public function getClockings(): Collection
    {
        return $this->clockings;
    }

    public function getDateEnd(): ?DateTimeInterface
    {
        return $this->dateEnd;
    }

    public function setDateEnd(?DateTimeInterface $dateEnd): void
    {
        $this->dateEnd = $dateEnd;
    }

    public function getDateStart(): ?DateTimeInterface
    {
        return $this->dateStart;
    }

    public function setDateStart(?DateTimeInterface $dateStart): void
    {
        $this->dateStart = $dateStart;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

}
