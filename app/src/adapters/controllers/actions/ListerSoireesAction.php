<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\dto\SoireeDTO;
use nrv\application\ports\api\SoireeServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /soirees, liste de toutes les soirées du festival,
 * triées par date puis par horaire.
 *
 * 200 : la liste et ses liens HATEOAS (vide s'il n'y a aucune soirée)
 */
class ListerSoireesAction extends AbstractAction
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
        try {
            $soirees = $this->service->getAllSoirees();
        } catch (\Throwable $e) {
            // aucune erreur client possible ici : toute exception est une erreur interne
            return $this->json($rs, ['error' => 'Erreur interne'], 500);
        }

        // liens HATEOAS construits à partir des noms de routes, pour ne pas écrire les URL en dur
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        // chaque soirée reçoit un lien self vers son détail
        $soireesJson = array_map(
            fn(SoireeDTO $s) => [
                ...$s->jsonSerialize(),
                'links' => [
                    'self' => ['href' => $routeParser->urlFor('soiree-detail', ['id' => $s->idSoiree])],
                ],
            ],
            $soirees
        );

        // même enveloppe que la liste des spectacles : les données et leurs liens
        return $this->json($rs, [
            'soirees' => $soireesJson,
            'links' => [
                'self' => ['href' => $routeParser->urlFor('soirees-list')],
            ],
        ]);
    }
}