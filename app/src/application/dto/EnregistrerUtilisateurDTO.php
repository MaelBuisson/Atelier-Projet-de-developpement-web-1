<?php

namespace nrv\application\dto;

final class EnregistrerUtilisateurDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $nom,
        public readonly string $prenom,
        public readonly string $password,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $texte = fn(string $key): string => is_string($data[$key] ?? null) ? trim($data[$key]) : '';

        return new self(
            $texte('email'),
            $texte('nom'),
            $texte('prenom'),
            is_string($data['password'] ?? null) ? $data['password'] : '' // jamais de trim
        );
    }
}