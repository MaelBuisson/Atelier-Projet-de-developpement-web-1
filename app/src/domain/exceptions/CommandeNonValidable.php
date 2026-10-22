<?php

namespace nrv\domain\exceptions;

use Exception;
use nrv\domain\entities\CommandeStatus;
use Throwable;

/**
 * Levée quand on valide une commande qui n'est plus un panier (déjà validée ou payée).
 * Le cas du panier vide a sa propre exception : PanierVide.
 * Code 409 (Conflict) : le statut de la commande ne permet pas la validation.
 */
class CommandeNonValidable extends Exception
{
    public function __construct(CommandeStatus $statut, ?Throwable $previous = null)
    {
        parent::__construct(
            "La commande ne peut être validée que si elle est dans le panier (statut actuel : {$statut->name}).",
            409,
            $previous
        );
    }
}
