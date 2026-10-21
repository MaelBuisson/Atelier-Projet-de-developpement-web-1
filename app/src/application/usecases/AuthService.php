<?php

namespace nrv\application\usecases;

use nrv\application\dto\AuthDTO;
use nrv\application\dto\ConnexionDTO;
use nrv\application\dto\UtilisateurDTO;
use nrv\application\exceptions\MauvaisMdpOuEmailException;
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
        // On verifie que l'email et le mot de passe ne sont pas vides
        if ($dto->email === '' || $dto->password === '') {
            throw new ValidationException("Email et mot de passe obligatoires.");
        }

        // On verifie que l'utilisateur existe
        $utilisateur = $this->repository->findByEmail(strtolower($dto->email));

        if ($utilisateur === null) {
            password_hash($dto->password, PASSWORD_DEFAULT);
            throw new MauvaisMdpOuEmailException("Email ou mot de passe incorrect.");
        }
        // On verifie que le mot de passe est correct
        if (!password_verify($dto->password, $utilisateur->getPasswordHash())) {
            throw new MauvaisMdpOuEmailException("Email ou mot de passe incorrect.");
        }

        // On retourne le token et les informations de l'utilisateur
        return new AuthDTO(
            $this->tokenProvider->generer($utilisateur),
            UtilisateurDTO::fromEntity($utilisateur)
        );
    }
}