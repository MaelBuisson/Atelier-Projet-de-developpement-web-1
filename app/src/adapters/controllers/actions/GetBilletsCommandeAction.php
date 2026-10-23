<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\dto\BilletDTO;
use nrv\application\ports\api\BilletServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /commandes/{id}/billets, les billets d'une commande payée de l'utilisateur connecté.
 * Route protégée par AuthMiddleware : seules les commandes de l'utilisateur du token sont prises en compte.
 *
 * 200 : la liste des billets (vide si la commande n'est pas payée, n'existe pas ou appartient à un autre utilisateur)
 * 400 : identifiant qui n'est pas un UUID
 * 401 : token absent, invalide ou expiré
 */
class GetBilletsCommandeAction extends AbstractAction
{
    public function __construct(
        private BilletServiceInterface $service
    ) {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        // posé par AuthMiddleware à partir du token JWT
        $userId = $rq->getAttribute('userId');
        if (!is_string($userId) || $userId === '') {
            return $this->json($rs, ['error' => 'Authentification requise.'], 401);
        }

        // validation avant la base : un id mal formé ferait échouer la requête SQL (colonne UUID)
        $id = $args['id'];
        if (!Uuid::isValid($id)) {
            return $this->json($rs, ['error' => "Identifiant de commande invalide : $id"], 400);
        }

        $billets = $this->service->getAllBilletByUtilisateur($id, $userId);

        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'billets' => array_map(fn(BilletDTO $billet) => [
                'billet' => $billet,
                'links' => [
                    'self' => ['href' => $routeParser->urlFor('get_billet', ['token' => $billet->reference])],
                ],
            ], $billets),
            'links' => [
                'self' => ['href' => $routeParser->urlFor('commande-billets', ['id' => $id])],
                'commande' => ['href' => $routeParser->urlFor('commande-detail', ['id' => $id])],
            ],
        ]);
    }
}