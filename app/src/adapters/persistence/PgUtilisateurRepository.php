<?php

namespace nrv\adapters\persistence;

use nrv\application\ports\spi\UtilisateurRepository;
use nrv\domain\entities\Utilisateur;
use nrv\domain\exceptions\UserNotFound;
use Override;

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

        return $this->toUtilisateur($row);
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

    /**
     * Retourne l'utilisateur correspondant à l'identifiant (UUID, issu du token JWT).
     *
     * @throws UserNotFound si aucun utilisateur ne porte cet identifiant
     */
    #[Override]
    public function findById(string $id): Utilisateur
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
            FROM utilisateur
            WHERE id_utilisateur = :id
            '
        );

        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new UserNotFound();
        }

        return $this->toUtilisateur($row);
    }

    // construit l'entité à partir d'une ligne de la table utilisateur (partagé par findByEmail et findById)
    private function toUtilisateur(array $row): Utilisateur
    {
        return new Utilisateur(
            $row['id_utilisateur'],
            $row['email'],
            $row['nom'],
            $row['prenom'],
            $row['password'],
            (int) $row['role']
        );
    }
}