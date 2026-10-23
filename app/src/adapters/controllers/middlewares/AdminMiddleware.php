<?php

namespace nrv\adapters\controllers\middlewares;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Réserve une route aux administrateurs.
 * À placer après AuthMiddleware, qui pose le rôle dans la requête.
 *
 * 403 : utilisateur authentifié mais non administrateur
 */
class AdminMiddleware implements MiddlewareInterface
{
    private const ROLE_ADMIN = 100; // à adapter à ta convention

    public function __construct(private ResponseFactoryInterface $responseFactory)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $role = $request->getAttribute('role');

        if ($role !== self::ROLE_ADMIN) {
            $response = $this->responseFactory->createResponse(403)
                ->withHeader('Content-Type', 'application/json');
            $response->getBody()->write(
                json_encode(['error' => 'Accès réservé aux administrateurs.'], JSON_UNESCAPED_UNICODE)
            );
            return $response;
        }

        return $handler->handle($request);
    }
}