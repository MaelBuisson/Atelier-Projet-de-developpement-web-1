<?php

namespace nrv\domain\exceptions;

use Exception;
use nrv\domain\entities\CommandeStatus;
use Throwable;

/**
 * Levée quand on ajoute ou retire des billets d'une commande qui n'est plus un panier
 * (déjà validée ou payée).
 * Code 409 (Conflict) : la commande existe, mais son statut ne permet plus de la modifier.
 */
class CommandeNonModifiable extends Exception
{
    public function __construct(CommandeStatus $statut, ?Throwable $previous = null)
    {
        parent::__construct(
            "La commande ne peut être modifiée que si elle est dans le panier (statut actuel : {$statut->name}).",
            409,
            $previous
        );
    }
}
