<?php

namespace nrv\application\ports\api;

use nrv\application\dto\PanierDTO;

interface CommandeServiceInterface
{
    public function getPanier(string $idUtilisateur): PanierDTO;
}