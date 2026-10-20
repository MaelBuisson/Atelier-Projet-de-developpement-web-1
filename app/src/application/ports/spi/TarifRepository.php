<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Tarif;

/**
 * Port de sortie (SPI) : accès aux tarifs, indépendant de la base de données utilisée.
 */
interface TarifRepository
{
    /**
     * @return Tarif[] tous les tarifs du festival (normal, réduit)
     */
    public function findAll(): array;
}