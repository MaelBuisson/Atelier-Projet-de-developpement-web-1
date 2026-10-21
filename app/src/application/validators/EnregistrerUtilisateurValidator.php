<?php

namespace nrv\application\validators;

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\exceptions\ValidationException;
class EnregistrerUtilisateurValidator
{

    public function valider(EnregistrerUtilisateurDTO $dto): void
    {
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Email invalide.");
        }
        if ($dto->nom === '' || $dto->prenom === '') {
            throw new ValidationException("Nom et prénom obligatoires.");
        }
        if (strlen($dto->password) < 8) {
            throw new ValidationException("Le mot de passe doit faire au moins 8 caractères.");
        }
    }
}