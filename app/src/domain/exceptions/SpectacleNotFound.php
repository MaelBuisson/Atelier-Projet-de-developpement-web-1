<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée lorsqu'aucun spectacle ne correspond à l'identifiant demandé.
 * L'action Slim la traduit en réponse HTTP 404.
 */
class SpectacleNotFound extends Exception
{
    public function __construct(string $id = '', ?Throwable $previous = null)
    {
        $message = $id === '' ? 'Spectacle introuvable' : "Spectacle introuvable : $id";
        parent::__construct($message, 404, $previous);
    }
}
