<?php

namespace nrv\application\ports\api;

use nrv\application\dto\SoireeDetailDTO;
use nrv\domain\exceptions\SoireeNotFound;

/**
 * Port d'entrée (API) : ce que l'application propose sur les soirées.
 * Les actions Slim n'utilisent que cette interface, jamais l'implémentation.
 */
interface SoireeServiceInterface
{
    /**
     * Détail d'une soirée : ses informations, ses tarifs et ses spectacles avec leurs artistes.
     *
     * @throws SoireeNotFound si aucune soirée ne porte cet identifiant
     */
    public function getSoireeDetail(string $id): SoireeDetailDTO;
}