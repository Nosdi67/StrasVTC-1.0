<?php

namespace App\Entity;

use App\Repository\SocieteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocieteRepository::class)]
class Societe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 70)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $adresse = null;

    #[ORM\Column(length: 70)]
    private ?string $telephone = null;

    /**
     * @var Collection<int, Chauffeur>
     */
    #[ORM\OneToMany(targetEntity: Chauffeur::class, mappedBy: 'societe')]
    private Collection $chauffeurs;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function __construct()
    {
        $this->chauffeurs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * @return Collection<int, Chauffeur>
     */
    public function getChauffeurs(): Collection
    {
        return $this->chauffeurs;
    }

    public function addChauffeur(Chauffeur $chauffeur): static
    {
        if (!$this->chauffeurs->contains($chauffeur)) {
            $this->chauffeurs->add($chauffeur);
            $chauffeur->setSociete($this);
        }

        return $this;
    }

    public function removeChauffeur(Chauffeur $chauffeur): static
    {
        if ($this->chauffeurs->removeElement($chauffeur)) {
            // set the owning side to null (unless already changed)
            if ($chauffeur->getSociete() === $this) {
                $chauffeur->setSociete(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
