<?php

namespace nrv\domain\entities;

use DateTime;

class Soiree
{
    private string $idSoiree;
    private string $nom;
    private DateTime $date;
    private DateTime $horaireDebut;
    private string $lieu;
    private int $nbPlace;

    public function __construct(string $id, DateTime $date, ?string $nom = null, ?DateTime $horaireDebut = null, ?string $lieu = null, ?int $nbPlace = 0)
    {
        $this->idSoiree = $id;
        $this->nom = $nom;
        $this->date = $date;
        $this->horaireDebut = $horaireDebut;
        $this->lieu = $lieu;
        $this->nbPlace = $nbPlace;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }
}