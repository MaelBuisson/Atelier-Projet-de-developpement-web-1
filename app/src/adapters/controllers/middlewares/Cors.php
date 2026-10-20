<?php
namespace nrv\adapters\controllers\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Middleware CORS : le client (localhost:7081) et l'API (localhost:7080) n'ont pas la même origine,
 * le navigateur bloque donc les requêtes fetch sans ces en-têtes.
 */
class Cors
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // méthodes autorisées sur la route demandée (disponibles car le routing middleware s'exécute avant)
        $routeContext = RouteContext::fromRequest($request);
        $routingResults = $routeContext->getRoutingResults();
        $methods = $routingResults->getAllowedMethods();
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

        // on renvoie l'origine du client plutôt que '*' : obligatoire avec Allow-Credentials
        $origin = $request->hasHeader('Origin') ? $request->getHeaderLine('Origin') : '*';

        // la requête est d'abord traitée, puis les en-têtes CORS sont ajoutés à la réponse
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', $requestHeaders)
            ->withHeader('Access-Control-Allow-Methods', implode(',', $methods))
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

}