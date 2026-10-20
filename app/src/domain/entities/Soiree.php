<?php

namespace nrv\domain\entities;

use DateTime;
use Respect\Validation\Validators\Date;

class Soiree
{
    private string $idSoiree;
    private string $nom;
    private Date $date;
    private DateTime $horaireDebut;
    private string $lieu;
    private int $nbPlace;

    public function __construct(string $id, string $nom, Date $date, DateTime $horaireDebut, string $lieu, int $nbPlace)
    {
        $this->idSoiree = $id;
        $this->nom = $nom;
        $this->date = $date;
        $this->horaireDebut = $horaireDebut;
        $this->lieu = $lieu;
        $this->nbPlace = $nbPlace;
    }
}