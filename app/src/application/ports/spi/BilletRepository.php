<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Billet;
interface BilletRepository
{
    public function getBillet(string $token): Billet;
}