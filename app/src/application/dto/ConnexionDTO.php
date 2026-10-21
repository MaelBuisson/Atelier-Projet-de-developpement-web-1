<?php

namespace nrv\application\dto;

final class ConnexionDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            is_string($data['email'] ?? null) ? trim($data['email']) : '',
            is_string($data['password'] ?? null) ? $data['password'] : ''
        );
    }
}