<?php

namespace nrv\adapters\controllers\middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


// Pour passer ce middleware, il faut utiliser un header HTTP Authorization de la forme :
// Authorization: Bearer {token}
// Ce middleware vérifie la validité du token JWT et transmet l'identité de l'utilisateur.

class AuthMiddleware
{
    public function __construct(
        private string $secret,
        private ResponseFactoryInterface $responseFactory
    ) {
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');

        // Le header doit ressembler à : "Bearer eyJhbGciOi..."
        if (!preg_match('/^Bearer\s+(\S+)$/', $header, $m)) {
            return $this->refuser('Token manquant.');
        }

        try {
            $payload = JWT::decode($m[1], new Key($this->secret, 'HS256'));
        } catch (\Throwable $e) {
            // signature invalide, token expiré, mal formé...
            return $this->refuser('Token invalide ou expiré.');
        }

        // On transmet l'identité à l'action via des attributs de la requête
        $request = $request
            ->withAttribute('userId', $payload->sub)
            ->withAttribute('role', $payload->role);

        return $handler->handle($request);
    }

    private function refuser(string $message): ResponseInterface
    {
        return JsonRenderer::render($this->responseFactory->createResponse(), 401, ['error' => $message]);
    }
}