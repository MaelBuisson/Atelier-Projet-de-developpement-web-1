<?php

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\exceptions\ValidationException;
use nrv\application\validators\EnregistrerUtilisateurValidator;

function verifier(array $o = []): void
{
    $d = array_merge([
        'email' => 'jean.dupont@mail.fr',
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'password' => 'motdepasse123',
    ], $o);

    (new EnregistrerUtilisateurValidator())->valider(
        new EnregistrerUtilisateurDTO($d['email'], $d['nom'], $d['prenom'], $d['password'])
    );
}

// ── cas valides ─

it('accepte des données valides', function () {
    expect(fn() => verifier())->not->toThrow(ValidationException::class);
});

it('accepte des emails valides', function (string $email) {
    expect(fn() => verifier(['email' => $email]))->not->toThrow(ValidationException::class);
})->with([
            'simple' => 'jean@mail.fr',
            'avec plus' => 'jean+nrv@mail.fr',
            'avec point' => 'jean.dupont@mail.fr',
            'sous-domaine' => 'jean@etu.univ-lorraine.fr',
            'majuscules' => 'Jean@Mail.FR',
            'chiffres' => 'jean123@mail123.fr',
        ]);

it('accepte des noms et prénoms non vides', function (string $valeur) {
    expect(fn() => verifier(['nom' => $valeur, 'prenom' => $valeur]))->not->toThrow(ValidationException::class);
})->with([
            'simple' => 'Dupont',
            'une lettre' => 'A',
            'accents' => 'Éloïse',
            'apostrophe' => "O'Brien",
            'tiret' => 'Jean-Pierre',
            'espace' => 'Anne Marie',
        ]);

it('accepte des mots de passe d\'au moins 8 caractères', function (string $password) {
    expect(fn() => verifier(['password' => $password]))->not->toThrow(ValidationException::class);
})->with([
            '8 caractères pile' => 'abcdefgh',
            'ponctuation' => 'P@ssw0rd!#',
            'tout en chiffres' => '12345678',
            'avec accents' => 'motdepassé1',
        ]);

// ── email invalide ─

it('rejette un email invalide', function (string $email) {
    expect(fn() => verifier(['email' => $email]))
        ->toThrow(ValidationException::class, 'Email invalide.');
})->with([
            'vide' => '',
            'sans arobase' => 'jeanmail.fr',
            'sans domaine' => 'jean@',
            'sans partie locale' => '@mail.fr',
            'double arobase' => 'jean@@mail.fr',
            'espace au milieu' => 'jean dupont@mail.fr',
            'espace au début' => ' jean@mail.fr',
            'injection sql' => "' OR 1=1 --@mail.fr",
            'html' => '<script>@mail.fr',
        ]);

// ── nom / prénom ─

it('rejette un nom ou un prénom vide', function (string $champ) {
    expect(fn() => verifier([$champ => '']))
        ->toThrow(ValidationException::class, 'Nom et prénom obligatoires.');
})->with(['nom', 'prenom']);

it('rejette un nom ou un prénom composé uniquement d\'espaces (après nettoyage par le DTO)', function (string $champ) {
    $dto = EnregistrerUtilisateurDTO::fromArray([
        'email' => 'a@b.fr',
        'nom' => 'A',
        'prenom' => 'B',
        'password' => 'motdepasse123',
        $champ => '   ',
    ]);

    expect(fn() => (new EnregistrerUtilisateurValidator())->valider($dto))
        ->toThrow(ValidationException::class, 'Nom et prénom obligatoires.');
})->with(['nom', 'prenom']);

// ── mot de passe ─

it('rejette un mot de passe de moins de 8 caractères', function (string $password) {
    expect(fn() => verifier(['password' => $password]))
        ->toThrow(ValidationException::class, 'au moins 8 caractères');
})->with([
            'vide' => '',
            '1 caractère' => 'a',
            '7 caractères' => 'abcdefg',
        ]);

// ── ordre des vérifications ─

it('vérifie l\'email avant le nom et le mot de passe', function () {
    expect(fn() => verifier(['email' => 'invalide', 'nom' => '', 'password' => 'a']))
        ->toThrow(ValidationException::class, 'Email invalide.');
});

it('vérifie le nom avant le mot de passe', function () {
    expect(fn() => verifier(['nom' => '', 'password' => 'a']))
        ->toThrow(ValidationException::class, 'Nom et prénom obligatoires.');
});

// ── champs absents ou mal typés (passés par fromArray) ─

it('rejette les champs absents ou mal typés', function (array $data) {
    $dto = EnregistrerUtilisateurDTO::fromArray($data);

    expect(fn() => (new EnregistrerUtilisateurValidator())->valider($dto))
        ->toThrow(ValidationException::class);
})->with([
            'tableau vide' => [[]],
            'email manquant' => [['nom' => 'A', 'prenom' => 'B', 'password' => 'motdepasse123']],
            'nom manquant' => [['email' => 'a@b.fr', 'prenom' => 'B', 'password' => 'motdepasse123']],
            'prénom manquant' => [['email' => 'a@b.fr', 'nom' => 'A', 'password' => 'motdepasse123']],
            'password manquant' => [['email' => 'a@b.fr', 'nom' => 'A', 'prenom' => 'B']],
            'email entier' => [['email' => 123, 'nom' => 'A', 'prenom' => 'B', 'password' => 'motdepasse123']],
            'nom null' => [['email' => 'a@b.fr', 'nom' => null, 'prenom' => 'B', 'password' => 'motdepasse123']],
            'prénom tableau' => [['email' => 'a@b.fr', 'nom' => 'A', 'prenom' => ['B'], 'password' => 'motdepasse123']],
            'password entier' => [['email' => 'a@b.fr', 'nom' => 'A', 'prenom' => 'B', 'password' => 12345678]],
            'password tableau' => [['email' => 'a@b.fr', 'nom' => 'A', 'prenom' => 'B', 'password' => ['x']]],
        ]);

it('est sans état (même résultat à chaque appel)', function () {
    $validateur = new EnregistrerUtilisateurValidator();
    $mauvais = new EnregistrerUtilisateurDTO('pas-un-email', 'A', 'B', 'motdepasse123');
    $bon = new EnregistrerUtilisateurDTO('a@b.fr', 'A', 'B', 'motdepasse123');

    expect(fn() => $validateur->valider($mauvais))->toThrow(ValidationException::class);
    expect(fn() => $validateur->valider($bon))->not->toThrow(ValidationException::class);
    expect(fn() => $validateur->valider($mauvais))->toThrow(ValidationException::class);
});