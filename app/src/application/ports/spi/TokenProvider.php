<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Utilisateur;

interface TokenProvider
{
    public function generer(Utilisateur $utilisateur): string;
}