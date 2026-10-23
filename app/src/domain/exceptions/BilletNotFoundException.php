<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée lorsqu'aucun billet ne correspond au token demandé.
 * L'action Slim la traduit en réponse HTTP 404.
 */
class BilletNotFoundException extends Exception
{
    public function __construct(string $id = '', ?Throwable $previous = null)
    {
        $message = $id === '' ? 'Billet introuvable' : "Billet introuvable : $id";
        parent::__construct($message, 404, $previous);
    }
}