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

    public function enregistrer(EnregistrerUtilisateurDTO $dto): UtilisateurDTO
    {
        $this->validator->valider($dto);

        $email = strtolower($dto->email);

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

}