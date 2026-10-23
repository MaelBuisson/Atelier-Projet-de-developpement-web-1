<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\CommandeRepository;
use nrv\domain\entities\Billet;
use nrv\domain\entities\Commande;
use nrv\domain\entities\CommandeStatus;
use nrv\domain\exceptions\PlacesInsuffisantes;

/**
 * Faux repository des commandes : les commandes sont gardées en mémoire, sans base de données.
 */
class FakeCommandeRepository implements CommandeRepository
{
    // nombre d'appels à save() : permet de vérifier qu'un use case enregistre (ou non) la commande
    public int $saveCalls = 0;
    // si renseignée, save() lève cette exception : simule une panne de la base de données
    public ?\Throwable $saveThrows = null;
    /** @var array<string, int> places restantes par id de soirée, pour simuler une soirée presque complète */
    public array $placesRestantes = [];
    /** @var Billet[] billets enregistrés par enregistrerPaiement() */
    public array $billets = [];

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

    // même contrat que PgCommandeRepository : la commande, quel que soit son statut, ou null
    public function findById(string $idCommande): ?Commande
    {
        return $this->commandes[$idCommande] ?? null;
    }

    /**
     * Même contrat que PgCommandeRepository : PlacesInsuffisantes si une soirée n'a pas assez de places
     * (et alors rien n'est enregistré), sinon la commande payée et ses billets sont enregistrés.
     * Places restantes par soirée dans $placesRestantes ; une soirée absente a des places illimitées.
     */
    public function enregistrerPaiement(Commande $commande, array $billets): void
    {
        foreach ($commande->getPlacesParSoiree() as $idSoiree => $nombre) {
            $restantes = $this->placesRestantes[$idSoiree] ?? PHP_INT_MAX;
            if ($nombre > $restantes) {
                throw new PlacesInsuffisantes($idSoiree, $restantes, $nombre);
            }
        }

        foreach ($commande->getPlacesParSoiree() as $idSoiree => $nombre) {
            if (isset($this->placesRestantes[$idSoiree])) {
                $this->placesRestantes[$idSoiree] -= $nombre;
            }
        }

        $this->commandes[$commande->getId()] = $commande;
        $this->billets = array_merge($this->billets, $billets);
    }
}
