<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDepart = null;

    #[ORM\Column(length: 250)]
    private ?string $adresseDepart = null;

    #[ORM\Column(length: 250)]
    private ?string $adresseArivee = null;

    #[ORM\Column (nullable: true)]
    private ?float $prix = null;

    #[ORM\Column]
    private ?int $nbPassager = null;

    #[ORM\Column(length: 255, nullable: true )]
    private ?string $devis = null;

    #[ORM\ManyToOne(inversedBy: 'course')]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    private ?Chauffeur $chauffeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nomCourse = null;

    #[ORM\Column(length: 20)]
    private ?string $publicId = null;

    #[ORM\Column(length: 30)]
    private ?string $vehicule = null;

    public function __construct()
    {
    $this->publicId = $this->generateUniquePublicId();
    }

    private function generateUniquePublicId(): string {
    return '#' . str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
   
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateDepart(): ?\DateTimeInterface
    {
        return $this->dateDepart;
    }

    public function setDateDepart(\DateTimeInterface $dateDepart): static
    {
        $this->dateDepart = $dateDepart;

        return $this;
    }

    public function getAdresseDepart(): ?string
    {
        return $this->adresseDepart;
    }

    public function setAdresseDepart(string $adresseDepart): static
    {
        $this->adresseDepart = $adresseDepart;

        return $this;
    }

    public function getAdresseArivee(): ?string
    {
        return $this->adresseArivee;
    }

    public function setAdresseArivee(string $adresseArivee): static
    {
        $this->adresseArivee = $adresseArivee;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNbPassager(): ?int
    {
        return $this->nbPassager;
    }

    public function setNbPassager(int $nbPassager): static
    {
        $this->nbPassager = $nbPassager;

        return $this;
    }

    public function getDevis(): ?string
    {
        return $this->devis;
    }

    public function setDevis(string $devis): static
    {
        $this->devis = $devis;

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getChauffeur(): ?Chauffeur
    {
        return $this->chauffeur;
    }

    public function setChauffeur(?Chauffeur $chauffeur): static
    {
        $this->chauffeur = $chauffeur;

        return $this;
    }

    public function getNomCourse(): ?string
    {
        return $this->adresseArivee . ' - '. $this->adresseDepart;
    }

    public function setNomCourse(?string $nomCourse): static
    {
        $this->nomCourse = $nomCourse;

        return $this;
    }

    public function getPublicId(): ?string
    {
        return $this->publicId;
    }

    public function setPublicId(string $publicId): static
    {
        $this->publicId = $publicId;

        return $this;
    }

    public function getVehicule(): ?string
    {
        return $this->vehicule;
    }

    public function setVehicule(string $vehicule): static
    {
        $this->vehicule = $vehicule;

        return $this;
    }
}
