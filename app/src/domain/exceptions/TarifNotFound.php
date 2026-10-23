<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

class TarifNotFound extends Exception
{
    public function __construct(string $nom = "", ?Throwable $previous = null)
    {
        $message = $nom === '' ? 'Tarif introuvable' : 'Tarif introuvable: '.$nom;
        parent::__construct($message, 404, $previous);
    }
}