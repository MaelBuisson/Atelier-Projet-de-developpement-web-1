<?php

namespace nrv\domain\entities;

use DateTime;

class Spectacle
{
    private string $idSpectacle;
    private string $titre;
    private string $description;
    private DateTime $horaire;
    private string $video;
    private string $styleMusique;
    private Soiree $soiree;

    private array $images = [];

    public function __construct(string $id, string $titre, DateTime $horaire, ?string $description = null, ?string $video = null, ?string $styleMusique = null)
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

    public function addImages(array $images): void
    {
        $this->images = array_merge($this->images, $images);
    }

    public function getImage(): ?Image
    {
        return $this->images[0] ?? null;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getSoiree(): Soiree
    {
        return $this->soiree;
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

    public function getHoraire(): DateTime
    {
        return $this->horaire;
    }

    public function getVideo(): ?string
    {
        return $this->video;
    }

    public function getStyleMusique(): ?string
    {
        return $this->styleMusique;
    }


}