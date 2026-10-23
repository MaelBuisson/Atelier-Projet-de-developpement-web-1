<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use nrv\domain\entities\CommandeStatus;
use nrv\domain\exceptions\CommandeNotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /commandes/{id}, une commande de l'utilisateur connecté (quel que soit son statut).
 * Route protégée par AuthMiddleware : seule une commande de l'utilisateur du token est renvoyée.
 *
 * 200 : la commande et ses liens (paiement si elle est validée)
 * 400 : identifiant qui n'est pas un UUID
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 404 : commande inexistante ou appartenant à un autre utilisateur
 */
class GetCommandeAction extends AbstractAction
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

        // validation avant la base : un id mal formé ferait échouer la requête SQL (colonne UUID)
        $id = $args['id'];
        if (!Uuid::isValid($id)) {
            return $this->json($rs, ['error' => "Identifiant de commande invalide : $id"], 400);
        }

        try {
            $commande = $this->service->getCommande($userId, $id);
        } catch (CommandeNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        }

        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        // liens selon le statut : ce que le client peut faire ensuite
        $links = ['self' => ['href' => $routeParser->urlFor('commande-detail', ['id' => $id])]];
        if ($commande->statut === CommandeStatus::VALIDEE->name) {
            $links['paiement'] = ['href' => $routeParser->urlFor('commande-paiement', ['id' => $id])];
        }

        return $this->json($rs, [
            'commande' => $commande,
            'links' => $links,
        ]);
    }
}
