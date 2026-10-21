<?php

namespace nrv\application\dto;

use nrv\application\dto\UtilisateurDTO;

// C'est le DTO qui est retourné à l'utilisateur après une authentification réussie. 
// Il contient le token JWT et les id de l'utilisateur.
final class AuthDTO implements \JsonSerializable
{
    public function __construct(
        public readonly string $token,
        public readonly UtilisateurDTO $utilisateur,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'token' => $this->token,
            'utilisateur' => $this->utilisateur,
        ];
    }
}