<?php

namespace nrv\application\usecases;

use DateTimeImmutable;
use nrv\application\dto\PanierDTO;
use nrv\application\ports\api\CommandeServiceInterface;
use nrv\application\ports\spi\CommandeRepository;
use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Commande;
use Ramsey\Uuid\Uuid;

class CommandeService implements CommandeServiceInterface
{
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly UtilisateurRepository $utilisateurRepository
    ) {
    }

    public function getPanier(string $idUtilisateur): PanierDTO
    {
        $commande = $this->commandeRepository->getPanier($idUtilisateur);
        if ($commande === null) {
            throw new \RuntimeException("Aucun panier trouvé pour l'utilisateur $idUtilisateur");
        }
        return PanierDTO::fromEntity($commande);
    }
}