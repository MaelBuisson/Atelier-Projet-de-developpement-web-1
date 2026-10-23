<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use nrv\domain\exceptions\CommandeNonModifiable;
use nrv\domain\exceptions\SoireeDansCommandeNotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;
use Ramsey\Uuid\Uuid;

/**
 * Adaptateur d'entrée : DELETE /panier/lignes/{idLigne}, retire un billet de la ligne du panier ;
 * la ligne disparaît quand elle n'a plus de billet.
 * Route protégée par AuthMiddleware : la ligne est cherchée uniquement dans le panier de l'utilisateur du token.
 *
 * 200 : le panier mis à jour et ses liens
 * 400 : identifiant de ligne qui n'est pas un UUID
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 404 : ligne absente du panier de l'utilisateur (y compris la ligne d'un autre utilisateur)
 * 409 : la commande n'est plus un panier
 */
class SupprimerDuPanierAction extends AbstractAction
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

        $idLigne = $args['idLigne'];
        if (!Uuid::isValid($idLigne)) {
            return $this->json($rs, ['error' => "Identifiant de la ligne invalide : $idLigne"], 400);
        }

        try {
            $panier = $this->service->supprimerDuPanier($userId, $idLigne);
        } catch (SoireeDansCommandeNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        } catch (CommandeNonModifiable $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 409);
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