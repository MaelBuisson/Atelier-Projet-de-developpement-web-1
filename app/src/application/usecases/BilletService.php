<?php

namespace nrv\application\usecases;

use nrv\application\dto\BilletDTO;
use nrv\application\ports\api\BilletServiceInterface;
use nrv\application\ports\spi\BilletRepository;
use nrv\domain\entities\Billet;

class BilletService implements BilletServiceInterface
{
    public function __construct(
        private BilletRepository $repository,
    ) {
    }

    public function getBillet(string $token): BilletDTO
    {
        $billet = $this->repository->getBillet($token);
        return BilletDTO::fromEntity($billet);
    }

    /** @return BilletDTO[] */
    public function getAllBilletByUtilisateur(string $idCommande, string $idUtilisateur): array
    {
        $billets = $this->repository->getBilletsByCommandePayee($idCommande, $idUtilisateur);
        return array_map(fn(Billet $b) => BilletDTO::fromEntity($b), $billets);
    }
}