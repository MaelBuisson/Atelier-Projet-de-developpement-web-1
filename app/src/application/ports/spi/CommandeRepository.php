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
}
