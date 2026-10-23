<?php

namespace nrv\application\ports\spi;

interface BilletRepository
{
    public function getBillet(string $token): array;
}