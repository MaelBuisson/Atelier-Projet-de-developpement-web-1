<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Billet;
use nrv\domain\entities\Commande;
use nrv\domain\exceptions\CommandeNonPayable;
use nrv\domain\exceptions\PlacesInsuffisantes;

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

    /**
     * Enregistre le paiement de la commande (statut PAYEE) et ses billets, en une seule transaction
     * qui vérifie d'abord, sous verrou, qu'il reste assez de places dans chaque soirée :
     * deux paiements simultanés ne peuvent pas vendre la même dernière place.
     *
     * @param Billet[] $billets les billets créés par Commande::payer()
     * @throws PlacesInsuffisantes si une soirée n'a plus assez de places (rien n'est enregistré)
     * @throws CommandeNonPayable si la commande n'est plus validée en base (paiement déjà enregistré)
     */
    public function enregistrerPaiement(Commande $commande, array $billets): void;

    /** @return Commande[] commandes de l'utilisateur (tous statuts), de la plus récente à la plus ancienne */
    public function findAllByUtilisateur(string $idUtilisateur): array;
}
