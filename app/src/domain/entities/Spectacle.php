<?php

namespace nrv\domain\entities;

use DateTimeImmutable;

class Spectacle
{
    private string $idSpectacle;
    private string $titre;
    private ?string $description;
    private DateTimeImmutable $horaire;
    private ?string $video;
    private string $styleMusique;
    private ?Soiree $soiree = null;

    /** @var Image[] */
    private array $images = [];

    /** @var Artiste[] */
    private array $artistes = [];

    public function __construct(string $id, string $titre, ?string $description, DateTimeImmutable $horaire, ?string $video, string $styleMusique)
    {
        $this->idSpectacle = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->horaire = $horaire;
        $this->video = $video;
        $this->styleMusique = $styleMusique;
    }

    public function assignSoiree(Soiree $soiree): void
    {
        $this->soiree = $soiree;
    }

    /** @param Image[] $images */
    public function addImages(array $images): void
    {
        $this->images = array_merge($this->images, $images);
    }

    /** @param Artiste[] $artistes */
    public function addArtistes(array $artistes): void
    {
        $this->artistes = array_merge($this->artistes, $artistes);
    }

    public function getId(): string
    {
        return $this->idSpectacle;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getHoraire(): DateTimeImmutable
    {
        return $this->horaire;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function getStyleMusique(): string
    {
        return $this->styleMusique;
    }

    public function getSoiree(): ?Soiree
    {
        return $this->soiree;
    }

    // première image, utilisée dans la liste des spectacles ; null si le spectacle n'a pas d'image
    public function getImage(): ?Image
    {
        return $this->images[0] ?? null;
    }

    /** @return Image[] */
    public function getImages(): array
    {
        return $this->images;
    }

    /** @return Artiste[] */
    public function getArtistes(): array
    {
        return $this->artistes;
    }
}
