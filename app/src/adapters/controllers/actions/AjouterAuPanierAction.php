<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\exceptions\ValidationException;
use nrv\application\ports\api\CommandeServiceInterface;
use nrv\application\validators\AjouterAuPanierValidator;
use nrv\domain\exceptions\CommandeNonModifiable;
use nrv\domain\exceptions\QuantiteInvalide;
use nrv\domain\exceptions\SoireeNotFound;
use nrv\domain\exceptions\TarifNotFound;
use nrv\domain\exceptions\UserNotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : POST /panier/lignes, ajout de billets au panier de l'utilisateur connecté.
 * Corps JSON : { "idSoiree": UUID, "tarif": "normal"|"reduit", "quantite": entier }.
 * Route protégée par AuthMiddleware : l'utilisateur vient du token, jamais du corps.
 * Le prix est celui du tarif enregistré en base, jamais une valeur envoyée par le client.
 *
 * 200 : le panier mis à jour et ses liens
 * 400 : corps absent ou invalide (validator), quantité refusée par le domaine
 * 401 : token absent, invalide ou expiré (réponse du middleware)
 * 404 : soirée, tarif ou utilisateur introuvable
 * 409 : la commande n'est plus un panier
 */
class AjouterAuPanierAction extends AbstractAction
{
    public function __construct(
        private CommandeServiceInterface $service,
        private AjouterAuPanierValidator $validator
    ) {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        // posé par AuthMiddleware à partir du token JWT
        $userId = $rq->getAttribute('userId');
        if (!is_string($userId) || $userId === '') {
            return $this->json($rs, ['error' => 'Authentification requise.'], 401);
        }

        $data = $rq->getParsedBody();
        if (!is_array($data)) {
            return $this->json($rs, ['error' => 'Corps de requête JSON attendu'], 400);
        }

        try {
            // forme des données d'abord : après le validator, les trois champs ont le bon type
            $this->validator->valider($data);
            $panier = $this->service->ajouterAuPanier($userId, $data['idSoiree'], $data['tarif'], $data['quantite']);
        } catch (UserNotFound | SoireeNotFound | TarifNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        } catch (ValidationException | QuantiteInvalide $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 400);
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