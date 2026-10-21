<?php

namespace nrv\application\usecases;

use nrv\application\dto\AuthDTO;
use nrv\application\dto\ConnexionDTO;
use nrv\application\dto\UtilisateurDTO;
use nrv\application\exceptions\IdentifiantsInvalidesException;
use nrv\application\exceptions\ValidationException;
use nrv\application\ports\api\AuthServiceInterface;
use nrv\application\ports\spi\TokenProvider;
use nrv\application\ports\spi\UtilisateurRepository;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private UtilisateurRepository $repository,
        private TokenProvider $tokenProvider
    ) {
    }

    public function connecter(ConnexionDTO $dto): AuthDTO
    {
        if ($dto->email === '' || $dto->password === '') {
            throw new ValidationException("Email et mot de passe obligatoires.");
        }

        $utilisateur = $this->repository->findByEmail(strtolower($dto->email));

        if ($utilisateur === null) {
            password_hash($dto->password, PASSWORD_DEFAULT);
            throw new IdentifiantsInvalidesException("Email ou mot de passe incorrect.");
        }

        if (!password_verify($dto->password, $utilisateur->getPasswordHash())) {
            throw new IdentifiantsInvalidesException("Email ou mot de passe incorrect.");
        }

        return new AuthDTO(
            $this->tokenProvider->generer($utilisateur),
            UtilisateurDTO::fromEntity($utilisateur)
        );
    }
}