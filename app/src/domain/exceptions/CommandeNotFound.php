<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée lorsqu'aucune commande ne correspond à l'identifiant demandé,
 * ou lorsqu'elle appartient à un autre utilisateur (on ne révèle pas qu'elle existe).
 * L'action Slim la traduit en réponse HTTP 404.
 */
class CommandeNotFound extends Exception
{
    public function __construct(string $id = '', ?Throwable $previous = null)
    {
        $message = $id === '' ? 'Commande introuvable' : "Commande introuvable : $id";
        parent::__construct($message, 404, $previous);
    }
}
