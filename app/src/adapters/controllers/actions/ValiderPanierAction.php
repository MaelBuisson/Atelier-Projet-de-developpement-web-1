<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\CommandeServiceInterface;
use nrv\domain\exceptions\PanierVide;
use nrv\domain\exceptions\CommandeNonValidable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : POST /panier/validation, le panier de l'utilisateur connecté devient une commande validée.
 * Pas de corps : tout vient du token. Route protégée par AuthMiddleware.
 *
 * 201 : la commande créée, son URL dans l'en-tête Location et ses liens
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 409 : panier vide (ou inexistant), ou commande qui n'est plus un panier
 */
class ValiderPanierAction extends AbstractAction
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
            $commande = $this->service->valider($userId);
        } catch (PanierVide | CommandeNonValidable $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 409);
        }

        // 201 Created : la validation crée une ressource (la commande), dont l'URL est donnée dans Location
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();
        $url = $routeParser->urlFor('commande-detail', ['id' => $commande->id]);

        // les réponses PSR-7 sont immuables : withHeader() renvoie une nouvelle réponse, à réaffecter
        $rs = $rs->withHeader('Location', $url);

        // étape suivante proposée au client : le paiement de la commande
        return $this->json($rs, [
            'commande' => $commande,
            'links' => [
                'self' => ['href' => $url],
                'paiement' => ['href' => $routeParser->urlFor('commande-paiement', ['id' => $commande->id])],
            ],
        ], 201);
    }
}