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
    // nombre d'appels à save() : permet de vérifier qu'un use case enregistre (ou non) la commande
    public int $saveCalls = 0;
    // si renseignée, save() lève cette exception : simule une panne de la base de données
    public ?\Throwable $saveThrows = null;

    /** @var array<string, Commande> commandes indexées par id, comme la clé primaire en base */
    private array $commandes = [];

    /** @param Commande[] $commandes */
    public function __construct(array $commandes = [])
    {
        foreach ($commandes as $commande) {
            $this->commandes[$commande->getId()] = $commande;
        }
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

    // comme l'upsert en base : ajoute la commande si son id est nouveau, la remplace sinon
    public function save(Commande $commande): void
    {
        $this->saveCalls++;
        if ($this->saveThrows !== null) {
            throw $this->saveThrows;
        }

        $this->commandes[$commande->getId()] = $commande;
    }
}
