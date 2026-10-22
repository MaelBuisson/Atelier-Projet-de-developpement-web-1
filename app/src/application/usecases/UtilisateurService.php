<?php

namespace nrv\application\usecases;

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\dto\UtilisateurDTO;
use nrv\application\exceptions\EmailDejaUtiliseException;
use nrv\application\ports\api\UtilisateurServiceInterface;
use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Utilisateur;
use nrv\application\validators\EnregistrerUtilisateurValidator;
use Ramsey\Uuid\Uuid;

class UtilisateurService implements UtilisateurServiceInterface
{
    public function __construct(
        private UtilisateurRepository $repository,
        private EnregistrerUtilisateurValidator $validator
    ) {
    }

    // Fonction pour enregistrer un nouvel utilisateur
    public function enregistrer(EnregistrerUtilisateurDTO $dto): UtilisateurDTO
    {
        // On vérifie que les données sont valides
        $this->validator->valider($dto);

        $email = strtolower($dto->email);

        // On s'assure que l'email n'est pas déjà utilisé
        if ($this->repository->findByEmail($email) !== null) {
            throw new EmailDejaUtiliseException("Cet email est déjà utilisé.");
        }

        $utilisateur = new Utilisateur(
            Uuid::uuid4()->toString(),
            $email,
            $dto->nom,
            $dto->prenom,
            password_hash($dto->password, PASSWORD_DEFAULT)
        );

        $this->repository->save($utilisateur);

        return UtilisateurDTO::fromEntity($utilisateur);
    }

    // l'entité devient un UtilisateurDTO : le hash du mot de passe ne sort pas de l'application ;
    // UserNotFound n'est pas interceptée, l'action la traduit en 404
    public function getUserById(string $id): UtilisateurDTO
    {
        return UtilisateurDTO::fromEntity($this->repository->findById($id));
    }
}