<?php

namespace nrv\adapters\persistence;

use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Utilisateur;

class PgUtilisateurRepository implements UtilisateurRepository
{
    public function __construct(private \PDO $pdo)
    {
    }

    // Fonction pour trouver un utilisateur par son email
    public function findByEmail(string $email): ?Utilisateur
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_utilisateur, email, nom, prenom, password, role
             FROM utilisateur WHERE email = :email"
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return new Utilisateur(
            $row['id_utilisateur'],
            $row['email'],
            $row['nom'],
            $row['prenom'],
            $row['password'],
            (int) $row['role']
        );
    }

    // Fonction pour enregistrer un nouvel utilisateur
    public function save(Utilisateur $u): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO utilisateur (id_utilisateur, email, nom, prenom, password, role)
             VALUES (:id, :email, :nom, :prenom, :password, :role)"
        );
        $stmt->execute([
            'id' => $u->getId(),
            'email' => $u->getEmail(),
            'nom' => $u->getNom(),
            'prenom' => $u->getPrenom(),
            'password' => $u->getPasswordHash(),
            'role' => $u->getRole(),
        ]);
    }
}