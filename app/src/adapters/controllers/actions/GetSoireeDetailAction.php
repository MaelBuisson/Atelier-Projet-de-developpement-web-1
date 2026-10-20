<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\SoireeServiceInterface;
use nrv\domain\exceptions\SoireeNotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /soirees/{id}, détail d'une soirée avec ses tarifs et ses spectacles.
 *
 * 200 : la soirée et ses liens HATEOAS
 * 400 : l'identifiant n'est pas un UUID
 * 404 : aucune soirée ne porte cet identifiant
 * Toute autre erreur remonte au middleware d'erreurs (500).
 */
class GetSoireeDetailAction extends AbstractAction
{
    public function __construct(
        private SoireeServiceInterface $service
    ) {
    }

    public function __invoke(
        ServerRequestInterface $rq,
        ResponseInterface $rs,
        array $args
    ): ResponseInterface {
        $id = $args['id'];

        // validation de l'entrée avant d'interroger la base : un id mal formé est une erreur du client
        if (!Uuid::isValid($id)) {
            return $this->json($rs, ['error' => "Identifiant de soirée invalide : $id"], 400);
        }

        try {
            $soiree = $this->service->getSoireeDetail($id);
        } catch (SoireeNotFound $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 404);
        }

        // liens HATEOAS construits à partir des noms de routes, pour ne pas écrire les URL en dur
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        return $this->json($rs, [
            'soiree' => $soiree,
            'links' => [
                'self' => ['href' => $routeParser->urlFor('soiree-detail', ['id' => $id])],
                'spectacles' => ['href' => $routeParser->urlFor('spectacles-list')],
            ],
        ]);
    }
}
