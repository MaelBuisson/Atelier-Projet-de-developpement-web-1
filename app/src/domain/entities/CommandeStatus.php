<?php

declare(strict_types=1);

namespace nrv\domain\entities;

// Ce sont les différents statuts possibles pour une commande.
// PANIER : la commande est en cours de création, l'utilisateur peut encore ajouter ou supprimer des soirées.
// VALIDEE : la commande a été validée par l'utilisateur, il ne peut plus modifier
// PAYEE : la commande a été payée, elle est finalisée et ne peut plus être modifiée.
enum CommandeStatus: int
{
    case PANIER = 0;
    case VALIDEE = 1;
    case PAYEE = 2;

    /**
     * Retrouve un statut à partir de son nom ('PANIER', 'VALIDEE', 'PAYEE'), tel qu'il est enregistré en base.
     * from() ne convient pas : il cherche la valeur entière (0, 1, 2), pas le nom.
     *
     * @throws \ValueError si le nom ne correspond à aucun statut
     */
    public static function fromName(string $nom): self
    {
        foreach (self::cases() as $status) {
            if ($status->name === $nom) {
                return $status;
            }
        }

        throw new \ValueError("Statut de commande inconnu : $nom");
    }
}