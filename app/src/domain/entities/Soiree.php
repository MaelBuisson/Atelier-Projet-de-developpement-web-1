<?php

namespace nrv\domain\entities;

use DateTimeImmutable;

class Soiree
{
    private string $idSoiree;
    private string $nom;
    private string $thematique;
    private DateTimeImmutable $date;
    private DateTimeImmutable $horaire;
    private string $lieu;
    private int $nbPlace;

    public function __construct(string $id, string $nom, string $thematique, DateTimeImmutable $date, DateTimeImmutable $horaire, string $lieu, int $nbPlace)
    {
        $this->idSoiree = $id;
        $this->nom = $nom;
        $this->thematique = $thematique;
        $this->date = $date;
        $this->horaire = $horaire;
        $this->lieu = $lieu;
        $this->nbPlace = $nbPlace;
    }

    public function getId(): string
    {
        return $this->idSoiree;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getThematique(): string
    {
        return $this->thematique;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getHoraire(): DateTimeImmutable
    {
        return $this->horaire;
    }

    public function getLieu(): string
    {
        return $this->lieu;
    }

    public function getNbPlace(): int
    {
        return $this->nbPlace;
    }
}
