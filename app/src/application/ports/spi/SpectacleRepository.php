<?php

namespace nrv\application\ports\spi;

interface SpectacleRepository
{

    public function findAll(): array;

}