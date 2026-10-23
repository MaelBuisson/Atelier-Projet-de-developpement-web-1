<?php

/*
 * Tests de CommandeStatus::fromName : conversion du statut enregistré en base (son nom) en cas de l'enum.
 */

use nrv\domain\entities\CommandeStatus;

it('retrouve chaque statut à partir de son nom', function (string $nom, CommandeStatus $attendu) {
    expect(CommandeStatus::fromName($nom))->toBe($attendu);
})->with([
    'panier' => ['PANIER', CommandeStatus::PANIER],
    'validée' => ['VALIDEE', CommandeStatus::VALIDEE],
    'payée' => ['PAYEE', CommandeStatus::PAYEE],
]);

it('rejette un nom de statut inconnu', function (string $nom) {
    CommandeStatus::fromName($nom);
})->throws(ValueError::class)->with([
    'minuscules' => 'panier',
    'valeur entière' => '1',
    'vide' => '',
]);
