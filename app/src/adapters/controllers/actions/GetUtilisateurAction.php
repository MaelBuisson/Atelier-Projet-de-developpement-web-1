<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\UtilisateurServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Slim\Routing\RouteContext;
use nrv\domain\exceptions\UserNotFound;

/**
 * Adaptateur d'entrée : GET /auth/me, informations de l'utilisateur connecté.
 * La route est protégée par AuthMiddleware : l'identifiant vient du token, jamais de la requête,
 * un utilisateur ne peut donc lire que son propre compte.
 *
 * 200 : l'utilisateur (sans mot de passe) et le lien self
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 404 : le compte n'existe plus alors que le token est encore valide
 */
class GetUtilisateurAction extends AbstractAction
{
    public function __construct(
        private UtilisateurServiceInterface $service
    ) {
    }

    public function __invoke(
        ServerRequestInterface $rq,
        ResponseInterface $rs,
        array $args
    ): ResponseInterface {
        // identifiant placé dans la requête par AuthMiddleware, à partir du token vérifié
        $id = $rq->getAttribute('userId');

        // protection supplémentaire : le token est signé, mais on ne passe à la base qu'un UUID valide
        if (!is_string($id) || !Uuid::isValid($id)) {
            return $this->json($rs, ['error' => "Identifiant d'utilisateur invalide dans le token"], 400);
        }

        try {
            $user = $this->service->getUserById($id);
        } catch (UserNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        }

        // même clé que la réponse de la connexion (AuthDTO) : le client lit toujours « utilisateur »
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'utilisateur' => $user,
            'links' => [
                'self' => ['href' => $routeParser->urlFor('me')],
            ],
        ]);
    }
}