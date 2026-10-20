<?php

namespace nrv\application\usecases;

use nrv\application\ports\api\SpectacleServiceInterface;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\application\dto\SpectacleDTO;

class SpectacleService implements SpectacleServiceInterface
{
    public function __construct(
        private SpectacleRepository $repository
    ) {
    }

    public function getAllSpectacles(): array
    {
        return array_map(
            fn($s) => SpectacleDTO::fromEntity($s),
            $this->repository->findAll()
        );
    }
}