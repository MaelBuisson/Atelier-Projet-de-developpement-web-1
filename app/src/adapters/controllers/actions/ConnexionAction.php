<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\dto\ConnexionDTO;
use nrv\application\exceptions\MauvaisMdpOuEmailException;
use nrv\application\exceptions\ValidationException;
use nrv\application\ports\api\AuthServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ConnexionAction extends AbstractAction
{
    public function __construct(
        private AuthServiceInterface $service
    ) {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $data = $rq->getParsedBody();
        if (!is_array($data)) {
            return $this->json($rs, ['error' => 'Corps de requête JSON attendu'], 400);
        }

        try {
            return $this->json($rs, $this->service->connecter(ConnexionDTO::fromArray($data)));
        } catch (ValidationException $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 400);
        } catch (MauvaisMdpOuEmailException $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 401);
        }
    }
}