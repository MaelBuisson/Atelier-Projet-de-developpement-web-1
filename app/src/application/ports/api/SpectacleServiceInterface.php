<?php

namespace nrv\application\ports\api;

/**
 * Port d'entrée (API) : ce que l'application propose sur les spectacles.
 * Les actions Slim n'utilisent que cette interface, jamais l'implémentation.
 */
interface SpectacleServiceInterface
{
    /**
     * @param string|null $style filtre sur le style de musique, null = tous les styles
     * @param string|null $date  filtre sur la date (Y-m-d), null = toutes les dates
     * @return \nrv\application\dto\SpectacleDTO[] les spectacles du programme correspondants
     */
    public function getAllSpectacles(?string $style = null, ?string $date = null): array;
}