<?php

namespace nrv\application\ports\api;

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\dto\UtilisateurDTO;

interface UtilisateurServiceInterface
{
    /**
     * @throws \nrv\application\exceptions\ValidationException
     * @throws \nrv\application\exceptions\EmailDejaUtiliseException
     */
    public function enregistrer(EnregistrerUtilisateurDTO $dto): UtilisateurDTO;
}