<?php

/*
 * Configuration de Pest.
 * Les tests sont écrits avec des fakes (tests/pest/fakes) à la place des repositories PostgreSQL :
 * ils vérifient la logique du domaine et des services sans base de données.
 */

use nrv\domain\entities\Artiste;
use nrv\domain\entities\Soiree;
use nrv\domain\entities\Spectacle;

/**
 * Crée une soirée de test ; seuls l'id et le nom changent d'un test à l'autre.
 */
function creerSoiree(string $id, string $nom = 'Rock Night'): Soiree
{
    return new Soiree(
        $id,
        $nom,
        'Rock indé',
        new DateTimeImmutable('2026-07-10'),
        DateTimeImmutable::createFromFormat('!H:i:s', '20:00:00'),
        'Parc de la Pépinière',
        500
    );
}

/**
 * Crée un spectacle de test à l'horaire donné (format H:i:s), avec ses artistes et son style.
 *
 * @param Artiste[] $artistes
 */
function creerSpectacle(string $id, string $titre, string $horaire, array $artistes = [], string $style = 'rock'): Spectacle
{
    $spectacle = new Spectacle(
        $id,
        $titre,
        "Description de $titre",
        DateTimeImmutable::createFromFormat('!H:i:s', $horaire),
        "https://video.example/$id",
        $style
    );
    $spectacle->addArtistes($artistes);

    return $spectacle;
}
