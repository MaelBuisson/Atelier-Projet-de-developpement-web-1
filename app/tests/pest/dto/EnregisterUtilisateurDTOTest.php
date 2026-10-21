<?php

use nrv\application\dto\EnregistrerUtilisateurDTO;

function dtoDepuis(array $o = []): EnregistrerUtilisateurDTO
{
    return EnregistrerUtilisateurDTO::fromArray(array_merge([
        'email' => 'jean@mail.fr',
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'password' => 'motdepasse123',
    ], $o));
}

it('conserve les valeurs valides telles quelles', function () {
    $dto = dtoDepuis();

    expect($dto->email)->toBe('jean@mail.fr')
        ->and($dto->nom)->toBe('Dupont')
        ->and($dto->prenom)->toBe('Jean')
        ->and($dto->password)->toBe('motdepasse123');
});

it('supprime les espaces autour de l\'email, du nom et du prénom', function () {
    $dto = dtoDepuis(['email' => '  jean@mail.fr ', 'nom' => "  Dupont\n", 'prenom' => "\tJean\r\n"]);

    expect($dto->email)->toBe('jean@mail.fr')
        ->and($dto->nom)->toBe('Dupont')
        ->and($dto->prenom)->toBe('Jean');
});

it('ne touche jamais au mot de passe', function (string $password) {
    expect(dtoDepuis(['password' => $password])->password)->toBe($password);
})->with([
            'espaces autour' => ' abc ',
            'retour ligne' => "abc\n",
            'tabulation' => "\tabc",
            'vide' => '',
        ]);

it('ne modifie pas la casse de l\'email (c\'est le rôle du service)', function () {
    expect(dtoDepuis(['email' => 'Jean@MAIL.fr'])->email)->toBe('Jean@MAIL.fr');
});

it('met une chaîne vide pour les champs absents', function () {
    $dto = EnregistrerUtilisateurDTO::fromArray([]);

    expect($dto->email)->toBe('')->and($dto->nom)->toBe('')
        ->and($dto->prenom)->toBe('')->and($dto->password)->toBe('');
});

it('met une chaîne vide pour les champs qui ne sont pas des chaînes', function (mixed $valeur) {
    $dto = EnregistrerUtilisateurDTO::fromArray([
        'email' => $valeur,
        'nom' => $valeur,
        'prenom' => $valeur,
        'password' => $valeur,
    ]);

    expect($dto->email)->toBe('')->and($dto->nom)->toBe('')
        ->and($dto->prenom)->toBe('')->and($dto->password)->toBe('');
})->with([
            'entier' => 123,
            'flottant' => 1.5,
            'booléen vrai' => true,
            'booléen faux' => false,
            'null' => null,
            'tableau' => [['a']],
            'objet' => new stdClass(),
        ]);

it('ignore les champs inconnus (pas de mass assignment)', function () {
    $dto = dtoDepuis(['role' => 99, 'id' => 'x', 'passwordHash' => 'y']);

    expect(get_object_vars($dto))->toHaveCount(4);
});

