<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Utilisateur;

/**
 * Port de sortie (SPI) : accès aux utilisateurs, indépendant de la base de données utilisée.
 */
interface UtilisateurRepository
{
    /**
     * @return Utilisateur|null null si aucun compte n'utilise cet email
     *                          (permet de vérifier qu'un email est libre et de refuser une connexion)
     */
    public function findByEmail(string $email): ?Utilisateur;

    public function save(Utilisateur $utilisateur): void;

    /**
     * @throws \nrv\domain\exceptions\UserNotFound si aucun utilisateur ne porte cet identifiant
     */
    public function findById(string $id): Utilisateur;
}