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
        // on renvoie l'origine du client plutôt que '*' : obligatoire avec Allow-Credentials
        $origin = $request->hasHeader('Origin') ? $request->getHeaderLine('Origin') : '*';
        $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers') ?: 'Content-Type, Authorization, X-Requested-With';

        // Tente de récupérer les méthodes autorisées si le routing a fonctionné
        try {
            $routeContext = RouteContext::fromRequest($request);
            $routingResults = $routeContext->getRoutingResults();
            $methods = $routingResults->getAllowedMethods();
        } catch (\Throwable $e) {
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        }

        $allowedMethods = !empty($methods) ? implode(',', $methods) : 'GET, POST, PUT, DELETE, PATCH, OPTIONS';

        // Traitement de la requête par la suite de l'application
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Headers', $requestHeaders)
            ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }
}
