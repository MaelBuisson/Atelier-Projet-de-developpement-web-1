<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée lorsqu'aucun utilisateur ne correspond à l'identifiant demandé
 * (par exemple un compte supprimé alors que son token est encore valide).
 * L'action Slim la traduit en réponse HTTP 404.
 */
class UserNotFound extends Exception
{
    public function __construct(string $id = '', ?Throwable $previous = null)
    {
        $message = $id === '' ? 'Utilisateur introuvable' : "Utilisateur introuvable : $id";
        parent::__construct($message, 404, $previous);
    }
}
