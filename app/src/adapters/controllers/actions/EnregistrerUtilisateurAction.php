<?php

namespace nrv\adapters\controllers\actions;

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\exceptions\EmailDejaUtiliseException;
use nrv\application\exceptions\ValidationException;
use nrv\application\ports\api\UtilisateurServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EnregistrerUtilisateurAction extends AbstractAction
{
    public function __construct(
        private UtilisateurServiceInterface $service
    ) {
    }

    public function __invoke(ServerRequestInterface $rq, ResponseInterface $rs, array $args): ResponseInterface
    {
        $data = $rq->getParsedBody();
        if (!is_array($data)) {
            return $this->json($rs, ['error' => 'Corps de requête JSON attendu'], 400);
        }

        try {
            $utilisateur = $this->service->enregistrer(EnregistrerUtilisateurDTO::fromArray($data));
            return $this->json($rs, $utilisateur, 201);
        } catch (ValidationException $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 400);
        } catch (EmailDejaUtiliseException $e) {
            return $this->json($rs, ['error' => $e->getMessage()], 409);
        }
    }
}