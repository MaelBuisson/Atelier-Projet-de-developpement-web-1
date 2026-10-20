<?php

namespace nrv\application\ports\api;

use nrv\application\dto\SoireeDetailDTO;
use nrv\domain\exceptions\SoireeNotFound;

interface SoireeServiceInterface
{
    public function getSoireeDetail(string $id): SoireeDetailDTO;
}