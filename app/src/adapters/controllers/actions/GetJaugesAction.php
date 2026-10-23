<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\BackofficeServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /backoffice/jauges, jauge et recette de chaque soirée.
 *
 * 200 : la liste des jauges
 * 401 : token absent ou invalide
 * 403 : utilisateur non administrateur
 */
class GetJaugesAction extends AbstractAction
{
    private const ROLE_ADMIN = 100;

    public function __construct(private BackofficeServiceInterface $service)
    {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'jauges' => $this->service->getJauges(),
            'links' => ['self' => ['href' => $routeParser->urlFor('backoffice-jauges')]],
        ]);
    }
}