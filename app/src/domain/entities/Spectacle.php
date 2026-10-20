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

    public function __construct(string $id, string $titre, string $description, DateTime $horaire, string $video, string $styleMusique)
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

    public function getImage(): Image
    {
        return $this->images[0];
    }

    public function getImages(): array
    {
        return $this->images;
    }

}