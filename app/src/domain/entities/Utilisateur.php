<?php

namespace nrv\domain\entities;

class Utilisateur
{
    public const ROLE_CLIENT = 1;

    public function __construct(
        private string $id,
        private string $email,
        private string $nom,
        private string $prenom,
        private string $passwordHash,
        private int $role = self::ROLE_CLIENT
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getNom(): string
    {
        return $this->nom;
    }
    public function getPrenom(): string
    {
        return $this->prenom;
    }
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }
    public function getRole(): int
    {
        return $this->role;
    }
}