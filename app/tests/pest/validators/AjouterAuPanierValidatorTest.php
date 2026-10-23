<?php

/*
 * Tests du validator de POST /panier/lignes : forme du corps JSON (idSoiree, tarif, quantite).
 * Le validator reçoit le tableau décodé tel quel : chaque champ peut manquer ou avoir n'importe quel type.
 */

use nrv\application\exceptions\ValidationException;
use nrv\application\validators\AjouterAuPanierValidator;

/**
 * Valide un corps de requête correct dans lequel on remplace ($modifs) ou retire ($retirer) des champs.
 * Nom unique : les fonctions des fichiers de test sont globales (verifier() existe déjà ailleurs).
 */
function validerAjoutPanier(array $modifs = [], array $retirer = []): void
{
    $data = array_merge([
        'idSoiree' => '10000000-0000-4000-8000-000000000001',
        'tarif' => 'normal',
        'quantite' => 2,
    ], $modifs);

    foreach ($retirer as $champ) {
        unset($data[$champ]);
    }

    (new AjouterAuPanierValidator())->valider($data);
}

// ── cas valides ─

it('accepte un corps valide', function () {
    expect(fn () => validerAjoutPanier())->not->toThrow(ValidationException::class);
});

it('accepte les deux tarifs', function (string $tarif) {
    expect(fn () => validerAjoutPanier(['tarif' => $tarif]))->not->toThrow(ValidationException::class);
})->with(['normal', 'reduit']);

it('accepte les quantités aux bornes', function (int $quantite) {
    expect(fn () => validerAjoutPanier(['quantite' => $quantite]))->not->toThrow(ValidationException::class);
})->with([
    'minimum' => 1,
    'maximum' => 10,
]);

it('ignore les champs supplémentaires', function () {
    // un idUtilisateur envoyé par le client n'est pas une erreur : l'action ne le lit jamais
    expect(fn () => validerAjoutPanier(['idUtilisateur' => 'autre', 'prix' => 0]))
        ->not->toThrow(ValidationException::class);
});

// ── idSoiree ─

it('rejette un idSoiree invalide', function (mixed $idSoiree) {
    expect(fn () => validerAjoutPanier(['idSoiree' => $idSoiree]))
        ->toThrow(ValidationException::class, 'idSoiree doit être un UUID valide.');
})->with([
    'texte' => 'pas-un-uuid',
    'vide' => '',
    'uuid tronqué' => '10000000-0000-4000-8000-00000000000',
    'nombre' => 123,
    'null' => [null],
    'tableau' => [['10000000-0000-4000-8000-000000000001']],
    'injection sql' => "' OR 1=1 --",
]);

it('rejette un corps sans idSoiree', function () {
    expect(fn () => validerAjoutPanier(retirer: ['idSoiree']))
        ->toThrow(ValidationException::class, 'idSoiree doit être un UUID valide.');
});

// ── tarif ─

it('rejette un tarif inconnu', function (mixed $tarif) {
    expect(fn () => validerAjoutPanier(['tarif' => $tarif]))
        ->toThrow(ValidationException::class, "tarif doit valoir 'normal' ou 'reduit'.");
})->with([
    'autre nom' => 'vip',
    'majuscules' => 'Normal',
    'espace' => 'normal ',
    'vide' => '',
    'nombre' => 1,
    // true == 'normal' en PHP : seul le in_array strict (3e argument true) refuse ce cas
    'booléen' => true,
    'null' => [null],
]);

it('rejette un corps sans tarif', function () {
    expect(fn () => validerAjoutPanier(retirer: ['tarif']))
        ->toThrow(ValidationException::class, "tarif doit valoir 'normal' ou 'reduit'.");
});

// ── quantite ─

it("rejette une quantité qui n'est pas un entier", function (mixed $quantite) {
    expect(fn () => validerAjoutPanier(['quantite' => $quantite]))
        ->toThrow(ValidationException::class, 'quantite doit être un nombre entier.');
})->with([
    'chiffre en texte' => '2',
    'texte' => 'abc',
    'décimal' => 1.5,
    'décimal rond' => 2.0,
    'booléen' => true,
    'null' => [null],
]);

it('rejette un corps sans quantité', function () {
    expect(fn () => validerAjoutPanier(retirer: ['quantite']))
        ->toThrow(ValidationException::class, 'quantite doit être un nombre entier.');
});

it('rejette une quantité hors des bornes', function (int $quantite) {
    expect(fn () => validerAjoutPanier(['quantite' => $quantite]))
        ->toThrow(ValidationException::class, 'quantite doit être comprise entre 1 et 10.');
})->with([
    'zéro' => 0,
    'négative' => -1,
    'juste au-dessus du maximum' => 11,
    'très grande' => 1000000,
]);

// ── ordre des contrôles ─

it('signale le premier champ invalide (idSoiree avant tarif et quantite)', function () {
    expect(fn () => validerAjoutPanier(['idSoiree' => 'x', 'tarif' => 'vip', 'quantite' => 0]))
        ->toThrow(ValidationException::class, 'idSoiree doit être un UUID valide.');
});

it('rejette un corps vide', function () {
    expect(fn () => (new AjouterAuPanierValidator())->valider([]))
        ->toThrow(ValidationException::class);
});
