<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Soiree;
use nrv\domain\exceptions\SoireeNotFound;

/**
 * Port de sortie (SPI) : accès aux soirées, indépendant de la base de données utilisée.
 */
interface SoireeRepository
{
    /**
     * @throws SoireeNotFound si aucune soirée ne porte cet identifiant
     */
    public function findById(string $id): Soiree;
}
