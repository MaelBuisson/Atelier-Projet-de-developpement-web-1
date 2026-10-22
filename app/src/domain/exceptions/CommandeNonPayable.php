<?php

namespace nrv\domain\exceptions;

use Exception;
use nrv\domain\entities\CommandeStatus;
use Throwable;

/**
 * Levée quand on paie une commande qui n'est pas au statut VALIDEE
 * (encore un panier, ou déjà payée).
 * Code 409 (Conflict) : le statut de la commande ne permet pas le paiement.
 */
class CommandeNonPayable extends Exception
{
    public function __construct(CommandeStatus $statut, ?Throwable $previous = null)
    {
        parent::__construct(
            "La commande ne peut être payée que si elle est validée (statut actuel : {$statut->name}).",
            409,
            $previous
        );
    }
}
