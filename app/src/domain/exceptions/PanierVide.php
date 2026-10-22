<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée quand on valide un panier qui ne contient aucune ligne.
 * Code 409 (Conflict) : la ressource existe, mais son état ne permet pas l'opération.
 */
class PanierVide extends Exception
{
    public function __construct(?Throwable $previous = null)
    {
        $message = 'Le panier est vide : impossible de valider la commande';
        parent::__construct($message, 409, $previous);
    }
}