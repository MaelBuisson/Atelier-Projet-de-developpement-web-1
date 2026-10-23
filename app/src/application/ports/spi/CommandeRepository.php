<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Commande;

/**
 * Port de sortie (SPI) : accès aux commandes, indépendant de la base de données utilisée.
 */
interface CommandeRepository
{
    /**
     * @return Commande|null le panier (commande au statut PANIER) de l'utilisateur avec ses lignes,
     *                       ou null s'il n'en a pas encore
     */
    public function getPanier(string $idUtilisateur): ?Commande;

    /**
     * Crée la commande ou la met à jour (statut, montant total) avec ses lignes,
     * en une seule transaction : les lignes retirées de l'objet sont supprimées.
     */
    public function save(Commande $commande): void;

    /**
     * @return Commande|null la commande (quel que soit son statut) avec ses lignes, ou null si elle n'existe pas
     */
    public function findById(string $idCommande): ?Commande;
}
