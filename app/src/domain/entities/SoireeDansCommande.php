<?php

namespace nrv\domain\entities;

class SoireeDansCommande
{
    private string $idSoiree;
    private Tarif $tarif;
    private int $quantite;

    public function __construct(string $idSoiree, Tarif $tarif, int $quantite)
    {
        $this->idSoiree = $idSoiree;
        $this->tarif = $tarif;
        if ($quantite <= 1) {
            throw new \InvalidArgumentException("La quantité doit être supérieure ou égale à 1.");
        } else {
            $this->quantite = $quantite;
        }
    }

    public function getMontant(): float
    {
        return $this->tarif->getPrix() * $this->quantite;
    }

    public function getIdSoiree(): string
    {
        return $this->idSoiree;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function getTarif(): Tarif
    {
        return $this->tarif;
    }
}