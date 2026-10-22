<?php

namespace nrv\domain\entities;

// Il s'agit d'une soirée dans une commande. Elle contient l'identifiant de la soirée, le tarif choisi et la quantité de billets.
class SoireeDansCommande
{
    private Soiree $soiree;
    private Tarif $tarif;
    private int $quantite;

    public function __construct(Soiree $soiree, Tarif $tarif, int $quantite)
    {
        $this->soiree = $soiree;
        $this->tarif = $tarif;
        if ($quantite <= 0) {
            throw new \InvalidArgumentException("La quantité doit être supérieure ou égale à 1.");
        } else {
            $this->quantite = $quantite;
        }
    }

    public function getMontant(): float
    {
        return $this->tarif->getPrix() * $this->quantite;
    }

    public function getSoiree(): Soiree
    {
        return $this->soiree;
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