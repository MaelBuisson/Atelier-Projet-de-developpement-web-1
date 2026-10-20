<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Spectacle;

/**
 * Port de sortie (SPI) : accès aux spectacles, indépendant de la base de données utilisée.
 */
interface SpectacleRepository
{
    /**
     * @return Spectacle[] tous les spectacles, chacun rattaché à sa soirée
     */
    public function findAll(): array;

    /**
     * @return Spectacle[] les spectacles de la soirée, tableau vide si elle n'en a aucun
     */
    public function getBySoiree(string $id): array;
}
