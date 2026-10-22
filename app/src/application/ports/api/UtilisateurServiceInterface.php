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

    /**
     * Informations de l'utilisateur connecté (route GET /auth/me), sans son mot de passe.
     *
     * @throws \nrv\domain\exceptions\UserNotFound si l'utilisateur n'existe plus
     */
    public function getUserById(string $id): UtilisateurDTO;
}