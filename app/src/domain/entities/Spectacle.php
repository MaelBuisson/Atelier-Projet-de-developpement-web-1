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

    public function __construct(string $id, string $titre, string $description, DateTime $horaire, string $video, string $styleMusique, Soiree $soiree)
    {
        $this->idSpectacle = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->horaire = $horaire;
        $this->video = $video;
        $this->styleMusique = $styleMusique;
        $this->soiree = $soiree;
    }
}