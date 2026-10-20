<?php

namespace nrv\domain\entities;

/**
 * Tarif unique du festival, en deux variantes : 'normal' et 'reduit'.
 * Le nom sert d'identifiant (clé primaire de la table tarif).
 */
class Tarif
{
    private string $nom;
    private float $prix;

    public function __construct(string $nom, float $prix)
    {
        $this->nom = $nom;
        $this->prix = $prix;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrix(): float
    {
        return $this->prix;
    }
}
