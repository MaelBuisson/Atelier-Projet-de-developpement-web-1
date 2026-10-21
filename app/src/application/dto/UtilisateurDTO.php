<?php

namespace nrv\application\dto;

use nrv\domain\entities\Utilisateur;

// DTO pour représenter un utilisateur dans l'application
// On ne renvoie pas le mot de passe pour des raisons de sécurité
final class UtilisateurDTO implements \JsonSerializable
{
    private function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly int $role,
    ) {
    }

    public static function fromEntity(Utilisateur $u): self
    {
        return new self($u->getId(), $u->getEmail(), $u->getNom(), $u->getPrenom(), $u->getRole());
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'role' => $this->role,
        ];
    }
}