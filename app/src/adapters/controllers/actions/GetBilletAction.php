<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\ports\api\BilletServiceInterface;
use nrv\domain\exceptions\BilletNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetBilletAction
{
    public function __construct(
        private readonly BilletServiceInterface $billetService,
    ) {
    }

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'] ?? '';

        try {
            $billet = $this->billetService->getBillet($token);
        } catch (BilletNotFoundException) {
            return $this->json($response, ['error' => 'Billet introuvable'], 404);
        }

        return $this->json($response, [
            'billet' => $billet,
            'links' => [
                'self' => ['href' => '/billets/' . $token],
            ],
        ], 200);
    }

    private function json(Response $response, array $data, int $status): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}