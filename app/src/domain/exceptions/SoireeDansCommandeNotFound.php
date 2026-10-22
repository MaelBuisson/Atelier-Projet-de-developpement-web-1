<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée lorsqu'une commande ne contient aucune ligne (soirée + tarif) avec l'identifiant demandé,
 * par exemple pour supprimer du panier une ligne qui n'y est pas.
 * L'action Slim la traduit en réponse HTTP 404.
 */
class SoireeDansCommandeNotFound extends Exception
{
    public function __construct(string $idLigne = '', ?Throwable $previous = null)
    {
        $message = $idLigne === ''
            ? 'Ligne de commande introuvable'
            : "Ligne de commande introuvable : $idLigne";
        parent::__construct($message, 404, $previous);
    }
}
