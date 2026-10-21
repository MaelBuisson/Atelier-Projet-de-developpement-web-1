<?php

namespace nrv\application\validators;

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\exceptions\ValidationException;
class EnregistrerUtilisateurValidator
{
    // Fonction pour valider les données d'enregistrement d'un utilisateur
    public function valider(EnregistrerUtilisateurDTO $dto): void
    {
        // Pour vérifier que c'est bien un email
        if (!filter_var($dto->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException("Email invalide.");
        }
        // Pour vérifier que le nom et le prénom ne sont pas vides
        if ($dto->nom === '' || $dto->prenom === '') {
            throw new ValidationException("Nom et prénom obligatoires.");
        }
        // Le mot de passe doit faire au moins 8 caractères
        if (strlen($dto->password) < 8) {
            throw new ValidationException("Le mot de passe doit faire au moins 8 caractères.");
        }
    }
}