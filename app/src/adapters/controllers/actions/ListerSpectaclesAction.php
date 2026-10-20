<?php

namespace nrv\adapters\controllers\actions;

use DateTimeImmutable;
use nrv\application\dto\SpectacleDTO;
use nrv\application\ports\api\SpectacleServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Adaptateur d'entrée : GET /spectacles, liste des spectacles du programme,
 * filtrable par style de musique et par date (paramètres de requête style et date).
 *
 * 200 : la liste, éventuellement vide si aucun spectacle ne correspond aux filtres
 * 400 : filtre invalide (date mal formée, style trop long)
 */
class ListerSpectaclesAction extends AbstractAction
{
    public function __construct(
        private SpectacleServiceInterface $service
    ) {
    }

    public function __invoke(
        ServerRequestInterface $rq,
        ResponseInterface $rs,
        array $args
    ): ResponseInterface {
        // filtres facultatifs : GET /spectacles?style=Metal&date=2027-07-09
        // un paramètre absent ou vide signifie « pas de filtre »
        $params = $rq->getQueryParams();

        // ?style[]=… donnerait un tableau au lieu d'une chaîne
        if (is_array($params['style'] ?? null) || is_array($params['date'] ?? null)) {
            return $this->json($rs, ['error' => 'Les filtres style et date doivent être des valeurs simples'], 400);
        }

        $style = trim((string) ($params['style'] ?? ''));
        $date = trim((string) ($params['date'] ?? ''));
        $style = $style === '' ? null : $style;
        $date = $date === '' ? null : $date;

        // style : 50 caractères au plus, comme la colonne style_musique
        if ($style !== null && mb_strlen($style) > 50) {
            return $this->json($rs, ['error' => 'Style de musique invalide : 50 caractères au plus'], 400);
        }

        // date : format Y-m-d et date réelle ; la comparaison après reformatage rejette
        // les dates qui « débordent », comme 2027-13-45 que PHP convertirait en 2028-02-14
        if ($date !== null) {
            $dateLue = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($dateLue === false || $dateLue->format('Y-m-d') !== $date) {
                return $this->json($rs, ['error' => "Date invalide : $date (format attendu AAAA-MM-JJ)"], 400);
            }
        }

        try {
            // les données seules : la réponse JSON n'est écrite qu'une fois, à la fin
            $spectacles = $this->service->getAllSpectacles($style, $date);
        } catch (\Throwable $e) {
            // aucune erreur client possible ici : toute exception est une erreur interne
            return $this->json($rs, ['error' => 'Erreur interne'], 500);
        }

        // liens HATEOAS construits à partir des noms de routes, pour ne pas écrire les URL en dur
        $routeParser = RouteContext::fromRequest($rq)->getRouteParser();

        // chaque spectacle reçoit un lien vers sa soirée : le client n'a pas à construire l'URL
        $spectaclesAvecLiens = array_map(
            fn (SpectacleDTO $s) => [
                ...$s->jsonSerialize(),
                'links' => [
                    'soiree' => ['href' => $routeParser->urlFor('soiree-detail', ['id' => $s->idSoiree])],
                ],
            ],
            $spectacles
        );

        // même enveloppe que le détail d'une soirée : les données et leurs liens ;
        // le lien self reprend les filtres utilisés (array_filter retire ceux qui valent null)
        return $this->json($rs, [
            'spectacles' => $spectaclesAvecLiens,
            'links' => [
                'self' => ['href' => $routeParser->urlFor('spectacles-list', [], array_filter(['style' => $style, 'date' => $date]))],
            ],
        ]);
    }
}