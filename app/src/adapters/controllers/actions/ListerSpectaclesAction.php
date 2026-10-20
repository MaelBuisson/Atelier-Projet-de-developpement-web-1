<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\SpectacleServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ListerSpectaclesAction extends AbstractAction
{
    public function __construct(
        private SpectacleServiceInterface $service
    ) {
    }

    public function __invoke(
        ServerRequestInterface $rq,
        ResponseInterface $rs,
        array $args
    ): ResponseInterface {
        try {
            return $this->json($rs, $this->service->getAllSpectacles());
        } catch (\Throwable $e) {
            return $this->json($rs, ['error' => 'Erreur interne'], 500);
        }
    }
}