<?php

/*
 * Tests de l'entité Spectacle : images, artistes et rattachement à une soirée.
 */

use nrv\domain\entities\Artiste;
use nrv\domain\entities\Image;
use nrv\domain\entities\Spectacle;

it('accepte une description et une vidéo absentes', function () {
    $spectacle = new Spectacle('p1', 'Concert', null, new DateTimeImmutable('21:00'), null, 'rock');

    expect($spectacle->getDescription())->toBeNull()
        ->and($spectacle->getVideo())->toBeNull();
});

it("retourne null comme image quand le spectacle n'en a pas", function () {
    expect(creerSpectacle('p1', 'Concert', '21:00:00')->getImage())->toBeNull();
});

it('retourne la première image ajoutée', function () {
    $spectacle = creerSpectacle('p1', 'Concert', '21:00:00');
    $spectacle->addImages([new Image('i1', 'premiere.jpg'), new Image('i2', 'seconde.jpg')]);

    expect($spectacle->getImage()->getUrl())->toBe('premiere.jpg')
        ->and($spectacle->getImages())->toHaveCount(2);
});

it('cumule les artistes ajoutés en plusieurs fois', function () {
    $spectacle = creerSpectacle('p1', 'Concert', '21:00:00', [new Artiste('a1', 'Doe', 'John')]);
    $spectacle->addArtistes([new Artiste('a2', 'The Roes')]);

    expect($spectacle->getArtistes())->toHaveCount(2)
        ->and($spectacle->getArtistes()[1]->getPrenom())->toBeNull();
});

it("n'a pas de soirée tant qu'elle n'est pas assignée", function () {
    $spectacle = creerSpectacle('p1', 'Concert', '21:00:00');
    expect($spectacle->getSoiree())->toBeNull();

    $soiree = creerSoiree('s1');
    $spectacle->assignSoiree($soiree);
    expect($spectacle->getSoiree())->toBe($soiree);
});
