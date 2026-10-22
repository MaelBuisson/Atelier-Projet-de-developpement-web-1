<?php

namespace nrv\application\usecases;

use DateTimeImmutable;
use nrv\application\dto\PanierDTO;
use nrv\application\ports\api\CommandeServiceInterface;
use nrv\application\ports\spi\CommandeRepository;
use nrv\domain\entities\Commande;
use Ramsey\Uuid\Uuid;
use nrv\domain\entities\Utilisateur;

class CommandeService implements CommandeServiceInterface
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
    ) {
    }

    public function getPanier(string $idUtilisateur): PanierDTO
    {
        $commande = $this->commandeRepository->getPanier($idUtilisateur);
        if ($commande === null) {
            $commande = new Commande(
                Uuid::uuid4()->toString(),
                new DateTimeImmutable(),
                new Utilisateur($idUtilisateur, '', '', '', '')
            );
        }
        return PanierDTO::fromEntity($commande);
    }
}