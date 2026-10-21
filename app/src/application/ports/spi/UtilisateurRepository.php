<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Utilisateur;

interface UtilisateurRepository
{
    public function findByEmail(string $email): ?Utilisateur;

    public function save(Utilisateur $utilisateur): void;
}