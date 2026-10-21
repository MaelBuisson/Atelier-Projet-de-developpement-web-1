<?php

namespace nrv\adapters\security;

use Firebase\JWT\JWT;
use nrv\application\ports\spi\TokenProvider;
use nrv\domain\entities\Utilisateur;

class JwtTokenProvider implements TokenProvider
{
    public function __construct(
        private string $secret,
        private int $dureeSecondes = 3600
    ) {
        if (strlen($secret) < 32) {
            throw new \InvalidArgumentException("JWT_SECRET doit faire au moins 32 caractères.");
        }
    }

    public function generer(Utilisateur $utilisateur): string
    {
        $maintenant = time();

        return JWT::encode([
            'iss' => 'nrv.net',
            'iat' => $maintenant,
            'exp' => $maintenant + $this->dureeSecondes,
            'sub' => $utilisateur->getId(),
            'role' => $utilisateur->getRole(),
        ], $this->secret, 'HS256');
    }
}