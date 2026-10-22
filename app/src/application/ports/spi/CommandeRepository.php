<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Commande;

interface CommandeRepository
{
    public function getPanier(string $idUtilisateur): ?Commande;
}