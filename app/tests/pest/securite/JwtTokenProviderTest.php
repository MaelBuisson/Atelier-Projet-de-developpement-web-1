<?php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use nrv\adapters\securite\JwtTokenProvider;
use nrv\domain\entities\Utilisateur;

const SECRET_TEST = 'un_secret_de_test_qui_fait_bien_plus_de_32_caracteres';

function utilisateurJwt(int $role = 1): Utilisateur
{
    return new Utilisateur('id-42', 'jean@mail.fr', 'Dupont', 'Jean', 'hash-secret', $role);
}

function decoderJwt(string $token, string $secret = SECRET_TEST): object
{
    return JWT::decode($token, new Key($secret, 'HS256'));
}

// ── construction ─

it('refuse un secret trop court', function (string $secret) {
    expect(fn() => new JwtTokenProvider($secret))->toThrow(InvalidArgumentException::class);
})->with([
            'vide' => '',
            'court' => 'secret',
            '31 caractères' => str_repeat('a', 31),
        ]);

it('accepte un secret de 32 caractères', function () {
    expect(new JwtTokenProvider(str_repeat('a', 32)))->toBeInstanceOf(JwtTokenProvider::class);
});

// ── contenu du token ─

it('génère un JWT à trois parties', function () {
    $token = (new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt());

    expect(explode('.', $token))->toHaveCount(3);
});

it('met l\'id, le rôle et l\'émetteur dans le payload', function () {
    $payload = decoderJwt((new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt(2)));

    expect($payload->sub)->toBe('id-42')
        ->and($payload->role)->toBe(2)
        ->and($payload->iss)->toBe('nrv.net');
});

it('expire après une heure par défaut', function () {
    $payload = decoderJwt((new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt()));

    expect($payload->exp - $payload->iat)->toBe(3600)
        ->and($payload->iat)->toBeLessThanOrEqual(time())
        ->and($payload->iat)->toBeGreaterThan(time() - 5);
});

it('respecte une durée personnalisée', function () {
    $payload = decoderJwt((new JwtTokenProvider(SECRET_TEST, 60))->generer(utilisateurJwt()));

    expect($payload->exp - $payload->iat)->toBe(60);
});

it('ne met ni email, ni nom, ni hash dans le token', function () {
    $token = (new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt());
    $payloadBrut = base64_decode(strtr(explode('.', $token)[1], '-_', '+/'));

    expect($payloadBrut)->not->toContain('jean@mail.fr')
        ->and($payloadBrut)->not->toContain('Dupont')
        ->and($payloadBrut)->not->toContain('hash-secret')
        ->and(array_keys(json_decode($payloadBrut, true)))->toBe(['iss', 'iat', 'exp', 'sub', 'role']);
});

// ── sécurité ─

it('est rejeté si on le vérifie avec un autre secret', function () {
    $token = (new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt());

    expect(fn() => decoderJwt($token, 'un_tout_autre_secret_de_plus_de_32_caracteres'))
        ->toThrow(SignatureInvalidException::class);
});

it('est rejeté quand on modifie le rôle dans le payload', function () {
    $token = (new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt(1));
    [$header, $payload, $signature] = explode('.', $token);

    $donnees = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
    $donnees['role'] = 2; // tentative de devenir admin
    $payloadTruque = rtrim(strtr(base64_encode(json_encode($donnees)), '+/', '-_'), '=');

    expect(fn() => decoderJwt("$header.$payloadTruque.$signature"))
        ->toThrow(SignatureInvalidException::class);
});

it('est rejeté quand la signature est supprimée ou modifiée', function (string $suffixe) {
    $token = (new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt());
    [$header, $payload] = explode('.', $token);

    expect(fn() => decoderJwt("$header.$payload.$suffixe"))->toThrow(\Exception::class);
})->with([
            'signature vide' => '',
            'signature bidon' => 'abcdef',
        ]);

it('est rejeté une fois expiré', function () {
    $token = (new JwtTokenProvider(SECRET_TEST, -10))->generer(utilisateurJwt());

    expect(fn() => decoderJwt($token))->toThrow(ExpiredException::class);
});

it('est rejeté si le vérificateur impose un autre algorithme', function () {
    $token = (new JwtTokenProvider(SECRET_TEST))->generer(utilisateurJwt());

    expect(fn() => JWT::decode($token, new Key(SECRET_TEST, 'HS512')))->toThrow(\Exception::class);
});

it('produit des tokens différents pour deux utilisateurs', function () {
    $provider = new JwtTokenProvider(SECRET_TEST);
    $autre = new Utilisateur('id-99', 'b@mail.fr', 'B', 'B', 'hash');

    expect($provider->generer(utilisateurJwt()))->not->toBe($provider->generer($autre));
});