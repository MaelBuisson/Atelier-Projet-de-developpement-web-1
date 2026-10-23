<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use nrv\domain\exceptions\CommandeNonPayable;
use nrv\domain\exceptions\CommandeNotFound;
use nrv\domain\exceptions\PlacesInsuffisantes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : POST /commandes/{id}/paiement, paiement simulé d'une commande validée.
 * Pas de corps : aucune donnée bancaire n'est demandée ni enregistrée (simulation).
 * Route protégée par AuthMiddleware : seule une commande de l'utilisateur du token peut être payée.
 *
 * 200 : la commande payée et ses liens
 * 400 : identifiant qui n'est pas un UUID
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 404 : commande inexistante ou appartenant à un autre utilisateur
 * 409 : commande non validée (panier ou déjà payée), ou places insuffisantes
 */
class PayerCommandeAction extends AbstractAction
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

        $id = $args['id'];
        if (!Uuid::isValid($id)) {
            return $this->json($rs, ['error' => "Identifiant de commande invalide : $id"], 400);
        }

        try {
            $commande = $this->service->payer($userId, $id);
        } catch (CommandeNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        } catch (CommandeNonPayable | PlacesInsuffisantes $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 409);
        }

        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'commande' => $commande,
            'links' => [
                'self' => ['href' => $routeParser->urlFor('commande-detail', ['id' => $id])],
            ],
        ]);
    }
}
