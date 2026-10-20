<?php

namespace nrv\application\usecases;

use nrv\application\dto\SoireeDetailDTO;
use nrv\application\ports\api\SoireeServiceInterface;
use nrv\application\ports\spi\SoireeRepository;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\application\ports\spi\TarifRepository;
use Override;

class SoireeService implements SoireeServiceInterface
{
    public function __construct(
        private SoireeRepository $soireeRepository,
        private SpectacleRepository $spectacleRepository,
        private TarifRepository $tarifRepository
    ) {
    }

    #[Override]
    public function getSoireeDetail(string $id): SoireeDetailDTO
    {
        $soiree = $this->soireeRepository->findById($id);
        $spectacles = $this->spectacleRepository->getBySoiree($id);
        foreach ($spectacles as $spectacle) {
            $spectacle->assignSoiree($soiree);
        }
        $tarifs = $this->tarifRepository->findBySoiree($id);

        return SoireeDetailDTO::fromEntities($soiree, $spectacles, $tarifs);
    }
}