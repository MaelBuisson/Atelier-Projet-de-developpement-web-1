<?php

/*
 * Tests du use case « liste des spectacles » (SpectacleService::getAllSpectacles).
 * Le repository est remplacé par un fake : les spectacles sont créés en mémoire,
 * rattachés à leur soirée comme le fait PgSpectacleRepository::findAll().
 */

use nrv\application\dto\SpectacleDTO;
use nrv\application\usecases\SpectacleService;
use nrv\domain\entities\Image;
use nrv\domain\entities\Soiree;
use tests\pest\fakes\FakeSpectacleRepository;

const ID_OUVERTURE = '10000000-0000-4000-8000-000000000001';
const ID_METAL = '10000000-0000-4000-8000-000000000003';

beforeEach(function () {
    $ouverture = creerSoiree(ID_OUVERTURE, 'Soirée d\'ouverture');
    // soirée à une autre date que creerSoiree(), pour vérifier que chaque spectacle prend la date de la sienne
    $metal = new Soiree(
        ID_METAL,
        'Nuit Métal',
        'Métal',
        new DateTimeImmutable('2027-07-09'),
        DateTimeImmutable::createFromFormat('!H:i:s', '20:30:00'),
        'L\'Autre Canal',
        1200
    );

    // styles : Hard Rock à l'ouverture et à la soirée métal, pour tester le filtre style + date
    $rolling = creerSpectacle('p1', 'Rolling Thunder', '20:00:00', [], 'Hard Rock');
    $rolling->addImages([new Image('i1', 'rolling-1.jpg'), new Image('i2', 'rolling-2.jpg')]);
    $rolling->assignSoiree($ouverture);

    $iron = creerSpectacle('p5', 'Iron Lorraine', '20:30:00', [], 'Metal');
    $iron->assignSoiree($metal);

    // spectacle sans image : le champ image doit valoir null
    $thunder = creerSpectacle('p7', 'Thunderstruck Nancy', '23:15:00', [], 'Hard Rock');
    $thunder->assignSoiree($metal);

    $this->service = new SpectacleService(new FakeSpectacleRepository([
        ID_OUVERTURE => [$rolling],
        ID_METAL => [$iron, $thunder],
    ]));
});

it('retourne un SpectacleDTO par spectacle', function () {
    expect($this->service->getAllSpectacles())
        ->toHaveCount(3)
        ->each->toBeInstanceOf(SpectacleDTO::class);
});

it("donne à chaque spectacle l'identifiant de sa soirée", function () {
    [$rolling, $iron, $thunder] = $this->service->getAllSpectacles();

    expect($rolling->idSoiree)->toBe(ID_OUVERTURE)
        ->and($iron->idSoiree)->toBe(ID_METAL)
        ->and($thunder->idSoiree)->toBe(ID_METAL);
});

it('prend la date de la soirée et formate l\'horaire du spectacle', function () {
    [$rolling, $iron] = $this->service->getAllSpectacles();

    expect($rolling->date)->toBe('2026-07-10')
        ->and($rolling->horaire)->toBe('20:00')
        ->and($iron->date)->toBe('2027-07-09')
        ->and($iron->horaire)->toBe('20:30');
});

it('utilise la première image, ou null si le spectacle n\'en a pas', function () {
    [$rolling, , $thunder] = $this->service->getAllSpectacles();

    expect($rolling->image)->toBe('rolling-1.jpg')
        ->and($thunder->image)->toBeNull();
});

it('produit le JSON attendu par le client', function () {
    $json = json_decode(json_encode($this->service->getAllSpectacles()), true);

    expect($json[0])->toBe([
        'id' => 'p1',
        'titre' => 'Rolling Thunder',
        'date' => '2026-07-10',
        'horaire' => '20:00',
        'image' => 'rolling-1.jpg',
        'idSoiree' => ID_OUVERTURE,
    ]);
});

it("retourne une liste vide quand il n'y a aucun spectacle", function () {
    $service = new SpectacleService(new FakeSpectacleRepository([]));

    expect($service->getAllSpectacles())->toBe([]);
});

// ── filtres (fonctionnalité 3) ─

it('retourne tous les spectacles sans filtre', function () {
    expect($this->service->getAllSpectacles(null, null))->toHaveCount(3);
});

it('filtre par style de musique', function () {
    $titres = array_map(fn ($s) => $s->titre, $this->service->getAllSpectacles('Hard Rock'));

    expect($titres)->toBe(['Rolling Thunder', 'Thunderstruck Nancy']);
});

it('filtre par style sans tenir compte des majuscules', function () {
    expect($this->service->getAllSpectacles('metal'))->toHaveCount(1)
        ->and($this->service->getAllSpectacles('METAL')[0]->titre)->toBe('Iron Lorraine');
});

it('filtre par date de soirée', function () {
    $resultat = $this->service->getAllSpectacles(null, '2027-07-09');

    expect($resultat)->toHaveCount(2)
        ->and(array_unique(array_map(fn ($s) => $s->date, $resultat)))->toBe(['2027-07-09']);
});

it('combine les filtres style et date', function () {
    $resultat = $this->service->getAllSpectacles('Hard Rock', '2027-07-09');

    expect($resultat)->toHaveCount(1)
        ->and($resultat[0]->titre)->toBe('Thunderstruck Nancy');
});

it('retourne une liste vide si aucun spectacle ne correspond', function () {
    expect($this->service->getAllSpectacles('Jazz'))->toBe([])
        ->and($this->service->getAllSpectacles(null, '2030-01-01'))->toBe([])
        ->and($this->service->getAllSpectacles('Metal', '2026-07-10'))->toBe([]);
});
