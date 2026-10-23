<?php

namespace nrv\application\usecases;

use nrv\application\dto\BilletDTO;
use nrv\application\ports\api\BilletServiceInterface;
use nrv\application\ports\spi\BilletRepository;

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
}