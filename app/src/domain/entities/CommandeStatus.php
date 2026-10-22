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
}