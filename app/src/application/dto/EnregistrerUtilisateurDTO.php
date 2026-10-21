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
        $get = fn(string $key): string => is_string($data[$key] ?? null) ? trim($data[$key]) : '';

        return new self($get('email'), $get('nom'), $get('prenom'), $get('password'));
    }
}