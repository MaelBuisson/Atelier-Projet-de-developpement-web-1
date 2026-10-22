<?php

namespace nrv\application\dto;

use nrv\domain\entities\Commande;
use nrv\domain\entities\SoireeDansCommande;

final class PanierDTO implements \JsonSerializable
{
    private function __construct(
        public readonly array $lignes,
        public readonly int $nbBillets,
        public readonly float $montantTotal,
    ) {
    }

    public static function fromEntity(Commande $c): self
    {
        $lignes = array_map(
            fn(SoireeDansCommande $l) => [
                'idSoiree' => $l->getSoiree()->getId(),
                'nomSoiree' => $l->getSoiree()->getNom(),
                'tarif' => $l->getTarif()->getNom(),
                'quantite' => $l->getQuantite(),
                'sousTotal' => round($l->getMontant(), 2),
                'links' => [
                    'soiree' => ['href' => '/soirees/' . $l->getSoiree()->getId()],
                ],
            ],
            $c->getSoireesDansCommande()
        );

        return new self(
            $lignes,
            array_sum(array_column($lignes, 'quantite')),
            round($c->getMontantTotal(), 2)
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'lignes' => $this->lignes,
            'nbBillets' => $this->nbBillets,
            'montantTotal' => $this->montantTotal,
        ];
    }
}