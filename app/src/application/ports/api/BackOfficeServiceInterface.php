<?php

namespace nrv\application\ports\api;

use nrv\application\dto\JaugeSoireeDTO;

interface BackofficeServiceInterface
{
    /** @return JaugeSoireeDTO[] */
    public function getJauges(): array;
}