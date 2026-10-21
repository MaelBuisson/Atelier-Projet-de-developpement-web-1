<?php

namespace nrv\application\ports\api;

use nrv\application\dto\AuthDTO;
use nrv\application\dto\ConnexionDTO;

interface AuthServiceInterface
{
    public function connecter(ConnexionDTO $dto): AuthDTO;
}