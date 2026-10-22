<?php

namespace nrv\application\dto;

use nrv\domain\entities\Commande;
use nrv\domain\entities\SoireeDansCommande;

/**
 * DTO du panier renvoyé au client : ses lignes, le nombre total de billets et le montant total.
 * Chaque ligne donne son identifiant (idLigne), nécessaire au client pour la supprimer.
 * Les liens HATEOAS seront ajoutés par l'action, qui connaît les routes.
 */
final class PanierDTO implements \JsonSerializable
{
    private function __construct(
        public readonly array $lignes,
        public readonly int $nbBillets,
        public readonly float $montantTotal,
    ) {
    }

    // montants arrondis au centime ; le nombre de billets est la somme des quantités des lignes
    public static function fromEntity(Commande $c): self
    {
        $lignes = array_map(
            fn(SoireeDansCommande $l) => [
                'idLigne' => $l->getIdLignes(),
                'idSoiree' => $l->getSoiree()->getId(),
                'nomSoiree' => $l->getSoiree()->getNom(),
                'tarif' => $l->getTarif()->getNom(),
                'quantite' => $l->getQuantite(),
                'sousTotal' => round($l->getMontant(), 2)
            ],
            $c->getSoireesDansCommande()
        );

        return new self(
            $lignes,
            array_sum(array_column($lignes, 'quantite')),
            round($c->getMontantTotal(), 2)
        );
    }

    // appelée automatiquement par json_encode grâce à JsonSerializable
    public function jsonSerialize(): array
    {
        return [
            'lignes' => $this->lignes,
            'nbBillets' => $this->nbBillets,
            'montantTotal' => $this->montantTotal,
        ];
    }
}