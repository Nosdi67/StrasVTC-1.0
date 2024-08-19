<?php
namespace App\Entity;

use App\Repository\PlanningRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanningRepository::class)]
class Planning
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dateDispo = null;

    #[ORM\OneToOne(inversedBy: 'planning', targetEntity: Chauffeur::class)]

    #[ORM\JoinColumn(nullable: false)]
    private ?Chauffeur $chauffeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDispo(): ?\DateTimeInterface
    {
        return $this->dateDispo;
    }

    public function setDateDispo(\DateTimeInterface $dateDispo): self
    {
        $this->dateDispo = $dateDispo;
        return $this;
    }

    public function getChauffeur(): ?Chauffeur
    {
        return $this->chauffeur;
    }

    public function setChauffeur(Chauffeur $chauffeur): self
    {
        $this->chauffeur = $chauffeur;
        return $this;
    }
}
