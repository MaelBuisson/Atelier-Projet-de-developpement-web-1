<?php

namespace nrv\domain\entities;

/**
 * Un billet : une place pour une soirée, à un tarif, achetée dans une commande payée.
 * Il est identifié par un token aléatoire (64 caractères hexadécimaux) imprimé sur le billet :
 * impossible à deviner, il sert de preuve d'achat à l'entrée de la soirée.
 */
class Billet
{
    public function __construct(
        private string $token,
        private string $idCommande,
        private Soiree $soiree,
        private Tarif $tarif,
        private Utilisateur $titulaire
    ) {
    }

    /**
     * Crée un billet avec un nouveau token : 32 octets aléatoires cryptographiquement sûrs,
     * écrits en hexadécimal (64 caractères, la taille de la colonne billet.token).
     */
    public static function nouveau(string $idCommande, Soiree $soiree, Tarif $tarif, Utilisateur $titulaire): self
    {
        return new self(bin2hex(random_bytes(32)), $idCommande, $soiree, $tarif, $titulaire);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getIdCommande(): string
    {
        return $this->idCommande;
    }

    public function getSoiree(): Soiree
    {
        return $this->soiree;
    }

    public function getTarif(): Tarif
    {
        return $this->tarif;
    }

    public function getTitulaire(): Utilisateur
    {
        return $this->titulaire;
    }
}
