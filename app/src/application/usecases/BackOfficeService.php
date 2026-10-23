<?php

namespace nrv\application\usecases;

use nrv\application\ports\api\BackofficeServiceInterface;
use nrv\application\ports\spi\JaugeRepository;


class BackofficeService implements BackofficeServiceInterface
{
    public function __construct(private JaugeRepository $repository)
    {
    }

    public function getJauges(): array
    {
        return $this->repository->getJauges();
    }
}