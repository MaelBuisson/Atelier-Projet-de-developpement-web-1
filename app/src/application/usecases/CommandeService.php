<?php

namespace nrv\application\usecases;

use DateTimeImmutable;
use nrv\application\dto\PanierDTO;
use nrv\application\ports\api\CommandeServiceInterface;
use nrv\application\ports\spi\CommandeRepository;
use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Commande;
use Ramsey\Uuid\Uuid;

/**
 * Use cases du panier et des commandes.
 */
class CommandeService implements CommandeServiceInterface
{
    // repositories injectés (PHP-DI) : le service ne dépend que des interfaces
    public function __construct(
        private readonly CommandeRepository $commandeRepository,
        private readonly UtilisateurRepository $utilisateurRepository,
    ) {
    }

    /**
     * Panier de l'utilisateur. S'il n'en a pas encore, un panier vide est renvoyé
     * (il n'est pas enregistré : il le sera au premier ajout de billets).
     *
     * @throws \nrv\domain\exceptions\UserNotFound si l'utilisateur du token n'existe plus
     */
    public function getPanier(string $idUtilisateur): PanierDTO
    {
        $commande = $this->commandeRepository->getPanier($idUtilisateur)
            ?? new Commande(
                Uuid::uuid4()->toString(),
                new DateTimeImmutable(),
                // le vrai utilisateur plutôt qu'un objet vide : l'entité Commande reste valide
                $this->utilisateurRepository->findById($idUtilisateur)
            );

        return PanierDTO::fromEntity($commande);
    }
}
