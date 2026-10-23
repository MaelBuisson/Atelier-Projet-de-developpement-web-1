<?php

namespace nrv\application\ports\spi;

use nrv\application\dto\JaugeSoireeDTO;

interface JaugeRepository
{
    /** @return JaugeSoireeDTO[] */
    public function getJauges(): array;
}