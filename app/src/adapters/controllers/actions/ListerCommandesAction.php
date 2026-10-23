<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * GET /commandes : commandes de l'utilisateur connecté (identifié par le token).
 * 200 : { commandes: [...], links }
 * 401 : token absent, invalide ou expiré
 */
class ListerCommandesAction extends AbstractAction
{
    public function __construct(private CommandeServiceInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $userId = $rq->getAttribute('userId');
        if (!is_string($userId) || $userId === '') {
            return $this->json($rs, ['error' => 'Authentification requise.'], 401);
        }

        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'commandes' => $this->service->getAllCommandesByUser($userId),
            'links' => [
                'self' => ['href' => $routeParser->urlFor('commandes-list')],
            ],
        ]);
    }
}