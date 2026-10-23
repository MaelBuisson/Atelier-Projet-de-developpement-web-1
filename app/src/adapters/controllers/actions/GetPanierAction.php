<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use nrv\domain\exceptions\UserNotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /panier, panier de l'utilisateur connecté.
 * Route protégée par AuthMiddleware : l'utilisateur vient du token, jamais de la requête.
 *
 * 200 : le panier (vide si l'utilisateur n'en a pas encore) et ses liens
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 404 : l'utilisateur du token n'existe plus
 */
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

        try {
            $panier = $this->service->getPanier($userId);
        } catch (UserNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        }

        // même enveloppe que les autres routes : les données et leurs liens
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'panier' => $panier,
            'links' => [
                'self' => ['href' => $routeParser->urlFor('panier-get')],
            ],
        ]);
    }
}
