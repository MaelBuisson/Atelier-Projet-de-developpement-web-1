<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée quand une ligne de commande est créée avec une quantité de billets nulle ou négative.
 * Code 400 (Bad Request) : la donnée envoyée par le client est invalide.
 */
class QuantiteInvalide extends Exception
{
    public function __construct(int $quantite, ?Throwable $previous = null)
    {
        parent::__construct(
            "La quantité doit être supérieure ou égale à 1 (reçu : $quantite).",
            400,
            $previous
        );
    }
}
