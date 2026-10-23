<?php

namespace nrv\application\dto;

use nrv\domain\entities\Commande;

/**
 * DTO d'une commande renvoyée au client : son identifiant (nécessaire pour la payer),
 * sa date de création, son statut ('PANIER', 'VALIDEE', 'PAYEE') et ses lignes.
 * Les lignes ont le même format que celles du panier (PanierDTO::lignesDe).
 */
final class CommandeDTO implements \JsonSerializable
{
    private function __construct(
        public readonly string $id,
        public readonly string $dateCreation,
        public readonly string $statut,
        public readonly array $lignes,
        public readonly int $nbBillets,
        public readonly float $montantTotal
    ) {
    }

    // statut : nom du cas de CommandeStatus, comme en base ; montant arrondi au centime comme dans PanierDTO
    public static function fromEntity(Commande $c): self
    {
        $lignes = PanierDTO::lignesDe($c);

        return new self(
            $c->getId(),
            $c->getDateCreation()->format('Y-m-d H:i:s'),
            $c->getStatus()->name,
            $lignes,
            array_sum(array_column($lignes, 'quantite')),
            round($c->getMontantTotal(), 2)
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'dateCreation' => $this->dateCreation,
            'statut' => $this->statut,
            'lignes' => $this->lignes,
            'nbBillets' => $this->nbBillets,
            'montantTotal' => $this->montantTotal,
        ];
    }
}
