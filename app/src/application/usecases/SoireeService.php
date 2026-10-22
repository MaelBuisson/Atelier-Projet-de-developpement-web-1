<?php

namespace nrv\application\usecases;

use nrv\application\dto\SoireeDetailDTO;
use nrv\application\dto\SoireeDTO;
use nrv\application\ports\api\SoireeServiceInterface;
use nrv\application\ports\spi\SoireeRepository;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\application\ports\spi\TarifRepository;
use Override;

/**
 * Use cases des soirées :
 * - « détail d'une soirée » : réunit la soirée, ses spectacles et ses tarifs,
 *   lus via les trois ports SPI, dans un SoireeDetailDTO ;
 * - « liste des soirées » : toutes les soirées, en SoireeDTO.
 */
class SoireeService implements SoireeServiceInterface
{
    // repositories injectés (PHP-DI) : le service ne dépend que des interfaces
    public function __construct(
        private SoireeRepository $soireeRepository,
        private SpectacleRepository $spectacleRepository,
        private TarifRepository $tarifRepository
    ) {
    }

    /**
     * SoireeNotFound n'est pas interceptée ici : elle remonte jusqu'à l'action,
     * qui la traduit en 404 (le use case ne connaît pas HTTP).
     */
    #[Override]
    public function getSoireeDetail(string $id): SoireeDetailDTO
    {
        $soiree = $this->soireeRepository->findById($id);
        $spectacles = $this->spectacleRepository->getBySoiree($id);

        // getBySoiree ne rattache pas la soirée : on le fait ici pour garder des entités cohérentes
        foreach ($spectacles as $spectacle) {
            $spectacle->assignSoiree($soiree);
        }
        $tarifs = $this->tarifRepository->findBySoiree($id);

        return SoireeDetailDTO::fromEntities($soiree, $spectacles, $tarifs);
    }

    /**
     * Use case « liste des soirées » : chaque entité devient un SoireeDTO (sans spectacles ni tarifs).
     */
    #[Override]
    public function getAllSoirees(): array
    {
        return array_map(
            fn($s) => SoireeDTO::fromEntity($s),
            $this->soireeRepository->findAll()
        );
    }
}