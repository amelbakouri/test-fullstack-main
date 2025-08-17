<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Project;
use App\Entity\Clocking;

#[ORM\Entity]
class ClockingEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $duration = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Clocking $clocking = null;


    public function getId(): ?int
    {
        return $this->id;
    }
    public function getProject(): ?Project
    {
        return $this->project;
    }
    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }
    public function getDuration(): ?int
    {
        return $this->duration;
    }
    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }
    public function getClocking(): ?Clocking
    {
        return $this->clocking;
    }
    public function setClocking(?Clocking $clocking): static
    {
        $this->clocking = $clocking;
        return $this;
    }
}
