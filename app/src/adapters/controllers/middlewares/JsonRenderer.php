<?php
namespace nrv\adapters\controllers\middlewares;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Écrit une réponse JSON : code HTTP, en-tête Content-Type et corps encodé.
 */
class JsonRenderer
{
    public static function render(Response $rs, int $code, mixed $data = null): Response
    {
        $rs = $rs->withStatus($code)
            ->withHeader('Content-Type', 'application/json;charset=utf-8');
        // options : accents et « / » non échappés, prix 35.0 gardés en décimal (et non 35)
        if (!is_null($data)) {
            $rs->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
        }
        return $rs;
    }
}