<?php

namespace nrv\application\usecases;

use nrv\application\ports\api\SpectacleServiceInterface;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\application\dto\SpectacleDTO;

/**
 * Use case « liste des spectacles » : lit les spectacles via le port SPI
 * et les transforme en DTO pour l'API.
 */
class SpectacleService implements SpectacleServiceInterface
{
    // le repository est injecté (PHP-DI) : le service ne connaît que l'interface, pas PostgreSQL
    public function __construct(
        private SpectacleRepository $repository
    ) {
    }

    public function getAllSpectacles(?string $style = null, ?string $date = null): array
    {
        return array_map(
            fn($s) => SpectacleDTO::fromEntity($s),
            $this->repository->findAll($style, $date)
        );
    }
}