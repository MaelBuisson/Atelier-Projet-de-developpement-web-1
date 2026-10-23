<?php

namespace nrv\domain\exceptions;

use Exception;
use Throwable;

/**
 * Levée au paiement lorsqu'une soirée de la commande n'a plus assez de places libres
 * pour tous les billets demandés. La commande reste validée (non payée).
 * L'action Slim la traduit en réponse HTTP 409.
 */
class PlacesInsuffisantes extends Exception
{
    public function __construct(string $nomSoiree, int $placesRestantes, int $placesDemandees, ?Throwable $previous = null)
    {
        parent::__construct(
            "Places insuffisantes pour la soirée « $nomSoiree » : $placesRestantes restante(s), $placesDemandees demandée(s).",
            409,
            $previous
        );
    }
}
