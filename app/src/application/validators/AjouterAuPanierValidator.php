<?php

namespace nrv\application\validators;

use nrv\application\exceptions\ValidationException;
use Ramsey\Uuid\Uuid;

/**
 * Valide la forme du corps de POST /panier/lignes avant l'appel au service :
 * idSoiree (UUID), tarif ('normal' ou 'reduit') et quantite (entier entre 1 et QUANTITE_MAX).
 * L'existence de la soirée et du tarif est vérifiée ensuite par le service (404).
 */
class AjouterAuPanierValidator
{
    // nombre maximal de billets ajoutés en une seule requête
    private const QUANTITE_MAX = 10;

    /**
     * @throws ValidationException au premier champ invalide, avec un message qui le désigne
     */
    public function valider(array $data): void
    {
        if (!is_string($data['idSoiree'] ?? null) || !Uuid::isValid($data['idSoiree'])) {
            throw new ValidationException('idSoiree doit être un UUID valide.');
        }

        if (!in_array($data['tarif'] ?? null, ['normal', 'reduit'], true)) {
            throw new ValidationException("tarif doit valoir 'normal' ou 'reduit'.");
        }

        if (!is_int($data['quantite'] ?? null)) {
            throw new ValidationException('quantite doit être un nombre entier.');
        }

        if ($data['quantite'] < 1 || $data['quantite'] > self::QUANTITE_MAX) {
            throw new ValidationException('quantite doit être comprise entre 1 et ' . self::QUANTITE_MAX . '.');
        }
    }
}