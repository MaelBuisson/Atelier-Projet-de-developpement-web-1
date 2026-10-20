<?php
namespace nrv\adapters\controllers\actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use nrv\adapters\controllers\middlewares\JsonRenderer;

/**
 * Classe mère des actions Slim (adaptateurs d'entrée) : une action par route,
 * avec des méthodes communes pour produire les réponses JSON.
 */
abstract class AbstractAction
{
    /**
     * Point d'entrée appelé par Slim ; $args contient les paramètres de la route (ex. ['id' => …]).
     */
    abstract public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface;

    // réponse JSON avec le code HTTP donné (200 par défaut)
    protected function json(ResponseInterface $rs, mixed $data, int $status = 200): ResponseInterface
    {
        return JsonRenderer::render($rs, $status, $data);
    }

    // réponse sans corps (204 No Content par défaut), par exemple après une suppression
    protected function empty(ResponseInterface $rs, int $status = 204): ResponseInterface
    {
        return JsonRenderer::render($rs, $status, null);
    }
}