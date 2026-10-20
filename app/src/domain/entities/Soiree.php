<?php

namespace nrv\domain\entities;

use DateTimeImmutable;

/**
 * Soirée du festival : un lieu, une date et une heure de début, une thématique,
 * et un nombre de places qui limite la vente des billets.
 * Ses spectacles et ses tarifs sont chargés à part par leurs repositories.
 */
class Soiree
{
    private string $idSoiree;
    private string $nom;
    private string $thematique;
    private DateTimeImmutable $date;
    // heure de début seule (colonne TIME) : la partie date vaut 1970-01-01
    private DateTimeImmutable $horaire;
    private string $lieu;
    // capacité totale de la soirée
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

    // ── accesseurs ─
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
