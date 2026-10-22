<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\CommandeRepository;
use nrv\domain\entities\Commande;
use nrv\domain\entities\CommandeStatus;

/**
 * Faux repository des commandes : les commandes sont gardées en mémoire, sans base de données.
 */
class FakeCommandeRepository implements CommandeRepository
{
    /** @param Commande[] $commandes */
    public function __construct(private array $commandes = [])
    {
    }

    // même contrat que PgCommandeRepository : la commande au statut PANIER de l'utilisateur, ou null
    public function getPanier(string $idUtilisateur): ?Commande
    {
        foreach ($this->commandes as $commande) {
            if ($commande->getUtilisateur()->getId() === $idUtilisateur
                && $commande->getStatus() === CommandeStatus::PANIER) {
                return $commande;
            }
        }
        return null;
    }
}
