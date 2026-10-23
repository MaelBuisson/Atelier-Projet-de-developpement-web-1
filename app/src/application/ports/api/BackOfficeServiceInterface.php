<?php

namespace nrv\application\ports\spi;

use nrv\application\dto\JaugeSoireeDTO;

interface BackofficeServiceInterface
{
    /** @return JaugeSoireeDTO[] */
    public function getJauges(): array;
}