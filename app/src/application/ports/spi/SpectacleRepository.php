<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Spectacle;

/**
 * Port de sortie (SPI) : accès aux spectacles, indépendant de la base de données utilisée.
 */
interface SpectacleRepository
{
    /**
     * @param string|null $style filtre sur le style de musique (insensible à la casse), null = pas de filtre
     * @param string|null $date  filtre sur la date de la soirée (Y-m-d), null = pas de filtre
     * @return Spectacle[] les spectacles correspondants, chacun rattaché à sa soirée
     */
    public function findAll(?string $style = null, ?string $date = null): array;

    /**
     * @return Spectacle[] les spectacles de la soirée, tableau vide si elle n'en a aucun
     */
    public function getBySoiree(string $id): array;
}
