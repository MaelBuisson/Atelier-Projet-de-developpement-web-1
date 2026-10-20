<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée lorsqu'aucune soirée ne correspond à l'identifiant demandé.
 * L'action Slim la traduit en réponse HTTP 404.
 */
class SoireeNotFound extends Exception
{
    public function __construct(string $id = '', ?Throwable $previous = null)
    {
        $message = $id === '' ? 'Soirée introuvable' : "Soirée introuvable : $id";
        parent::__construct($message, 404, $previous);
    }
}
