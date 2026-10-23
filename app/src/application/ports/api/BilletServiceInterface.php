<?php

namespace nrv\application\ports\api;

interface BilletServiceInterface
{
    public function getBillet(string $token): array;
}