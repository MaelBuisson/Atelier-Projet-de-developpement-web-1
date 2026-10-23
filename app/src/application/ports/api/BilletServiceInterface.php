<?php

namespace nrv\application\ports\api;

use nrv\application\dto\BilletDTO;

interface BilletServiceInterface
{
    public function getBillet(string $token): BilletDTO;

    public function getAllBilletByUtilisateur(string $idCommande, string $idUtilisateur): array;
}