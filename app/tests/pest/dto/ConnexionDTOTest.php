<?php

use nrv\application\dto\ConnexionDTO;

it('conserve les valeurs valides', function () {
    $dto = ConnexionDTO::fromArray(['email' => 'jean@mail.fr', 'password' => 'motdepasse123']);

    expect($dto->email)->toBe('jean@mail.fr')
        ->and($dto->password)->toBe('motdepasse123');
});

it('supprime les espaces autour de l\'email', function () {
    $dto = ConnexionDTO::fromArray(['email' => "  jean@mail.fr\n", 'password' => 'x']);

    expect($dto->email)->toBe('jean@mail.fr');
});

it('ne touche jamais au mot de passe', function (string $password) {
    expect(ConnexionDTO::fromArray(['email' => 'a@b.fr', 'password' => $password])->password)
        ->toBe($password);
})->with([
            'espaces autour' => ' abc ',
            'retour ligne' => "abc\n",
            'tabulation' => "\tabc",
        ]);

it('ne modifie pas la casse de l\'email (c\'est le rôle du service)', function () {
    expect(ConnexionDTO::fromArray(['email' => 'Jean@MAIL.fr', 'password' => 'x'])->email)
        ->toBe('Jean@MAIL.fr');
});

it('met une chaîne vide pour les champs absents', function () {
    $dto = ConnexionDTO::fromArray([]);

    expect($dto->email)->toBe('')->and($dto->password)->toBe('');
});

it('met une chaîne vide pour les champs qui ne sont pas des chaînes', function (mixed $valeur) {
    $dto = ConnexionDTO::fromArray(['email' => $valeur, 'password' => $valeur]);

    expect($dto->email)->toBe('')->and($dto->password)->toBe('');
})->with([
            'entier' => 123,
            'flottant' => 1.5,
            'booléen' => true,
            'null' => null,
            'tableau' => [['a']],
            'objet' => new stdClass(),
        ]);

it('ignore les champs inconnus', function () {
    $dto = ConnexionDTO::fromArray(['email' => 'a@b.fr', 'password' => 'x', 'role' => 99]);

    expect(get_object_vars($dto))->toHaveCount(2);
});