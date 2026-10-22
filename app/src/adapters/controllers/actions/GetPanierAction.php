<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class GetPanierAction extends AbstractAction
{
    public function __construct(
        private CommandeServiceInterface $service
    ) {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        // posé par AuthMiddleware à partir du token JWT
        $userId = $rq->getAttribute('userId');
        if (!is_string($userId) || $userId === '') {
            return $this->json($rs, ['error' => 'Authentification requise.'], 401);
        }

        return $this->json($rs, $this->service->getPanier($userId));
    }
}