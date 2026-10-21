<?php

/*
 * Tests des use cases des soirées : détail (getSoireeDetail) et liste (getAllSoirees).
 * Les repositories sont remplacés par des fakes : le service ne dépend que des ports SPI.
 */

use nrv\application\dto\SoireeDTO;
use nrv\application\dto\SoireeDetailDTO;
use nrv\application\dto\SpectacleDetailDTO;
use nrv\application\usecases\SoireeService;
use nrv\domain\entities\Artiste;
use nrv\domain\entities\Soiree;
use nrv\domain\entities\Tarif;
use nrv\domain\exceptions\SoireeNotFound;
use tests\pest\fakes\FakeSoireeRepository;
use tests\pest\fakes\FakeSpectacleRepository;
use tests\pest\fakes\FakeTarifRepository;

const ID_ROCK = '11111111-1111-1111-1111-111111111111';
const ID_JAZZ = '22222222-2222-2222-2222-222222222222';
const ID_VIDE = '33333333-3333-3333-3333-333333333333';

beforeEach(function () {
    $this->soireeRock = creerSoiree(ID_ROCK, 'Rock Night');
    $this->soireeJazz = creerSoiree(ID_JAZZ, 'Jazz Club');

    // un artiste présent dans deux spectacles de la soirée rock
    $doe = new Artiste('a1', 'Doe', 'John');
    $roes = new Artiste('a2', 'The Roes');

    $this->spectaclesRock = [
        creerSpectacle('p1', 'Concert A', '21:00:00', [$doe]),
        creerSpectacle('p2', 'Concert B', '22:30:00', [$doe, $roes]),
    ];

    $this->service = new SoireeService(
        new FakeSoireeRepository([$this->soireeRock, $this->soireeJazz, creerSoiree(ID_VIDE, 'Soirée vide')]),
        new FakeSpectacleRepository([
            ID_ROCK => $this->spectaclesRock,
            ID_JAZZ => [creerSpectacle('p3', 'Concert Jazz', '20:00:00')],
        ]),
        new FakeTarifRepository([
            ID_ROCK => [new Tarif('normal', 20.0), new Tarif('reduit', 12.0)],
            ID_JAZZ => [new Tarif('normal', 35.0), new Tarif('reduit', 25.0)],
        ])
    );
});

it('retourne les informations de la soirée demandée', function () {
    $detail = $this->service->getSoireeDetail(ID_ROCK);

    expect($detail)->toBeInstanceOf(SoireeDetailDTO::class)
        ->and($detail->idSoiree)->toBe(ID_ROCK)
        ->and($detail->nom)->toBe('Rock Night')
        ->and($detail->thematique)->toBe('Rock indé')
        ->and($detail->date)->toBe('2026-07-10')
        ->and($detail->horaire)->toBe('20:00')
        ->and($detail->lieu)->toBe('Parc de la Pépinière');
});

it('contient les spectacles de la soirée avec leurs artistes', function () {
    $detail = $this->service->getSoireeDetail(ID_ROCK);

    expect($detail->spectacles)->toHaveCount(2)
        ->each->toBeInstanceOf(SpectacleDetailDTO::class);

    [$a, $b] = $detail->spectacles;
    expect($a->titre)->toBe('Concert A')
        ->and($a->horaire)->toBe('21:00')
        ->and($a->artistes)->toBe(['John Doe'])
        // artiste sans prénom : pas d'espace en trop devant le nom
        ->and($b->artistes)->toBe(['John Doe', 'The Roes']);
});

it('ne retourne que les tarifs de la soirée demandée', function () {
    $rock = $this->service->getSoireeDetail(ID_ROCK);
    $jazz = $this->service->getSoireeDetail(ID_JAZZ);

    expect($rock->tarifs)->toBe([
        ['nom' => 'normal', 'prix' => 20.0],
        ['nom' => 'reduit', 'prix' => 12.0],
    ])->and($jazz->tarifs)->toBe([
        ['nom' => 'normal', 'prix' => 35.0],
        ['nom' => 'reduit', 'prix' => 25.0],
    ]);
});

it("ne mélange pas les spectacles d'une autre soirée", function () {
    $jazz = $this->service->getSoireeDetail(ID_JAZZ);

    expect($jazz->spectacles)->toHaveCount(1)
        ->and($jazz->spectacles[0]->titre)->toBe('Concert Jazz');
});

it('retourne des listes vides pour une soirée sans spectacle ni tarif', function () {
    $detail = $this->service->getSoireeDetail(ID_VIDE);

    expect($detail->nom)->toBe('Soirée vide')
        ->and($detail->spectacles)->toBe([])
        ->and($detail->tarifs)->toBe([]);
});

it('rattache chaque spectacle à sa soirée', function () {
    $this->service->getSoireeDetail(ID_ROCK);

    foreach ($this->spectaclesRock as $spectacle) {
        expect($spectacle->getSoiree())->toBe($this->soireeRock);
    }
});

it("lève SoireeNotFound si la soirée n'existe pas", function () {
    $this->service->getSoireeDetail('99999999-9999-9999-9999-999999999999');
})->throws(SoireeNotFound::class, 'Soirée introuvable');

it('produit le JSON attendu par le client', function () {
    $json = json_decode(json_encode($this->service->getSoireeDetail(ID_ROCK)), true);

    expect($json)->toHaveKeys(['id', 'nom', 'thematique', 'date', 'horaire', 'lieu', 'tarifs', 'spectacles'])
        ->and($json['spectacles'][0])->toHaveKeys(
            ['id', 'titre', 'artistes', 'description', 'horaire', 'video', 'styleMusique']
        );
});

// ── liste des soirées (getAllSoirees) ─

/**
 * Service dont le repository contient les soirées données, sans spectacle ni tarif.
 *
 * @param Soiree[] $soirees
 */
function serviceAvecSoirees(array $soirees): SoireeService
{
    return new SoireeService(
        new FakeSoireeRepository($soirees),
        new FakeSpectacleRepository(),
        new FakeTarifRepository()
    );
}

/**
 * Soirée de test à la date et à l'heure données (Y-m-d et H:i:s).
 */
function soireeLe(string $id, string $nom, string $date, string $horaire): Soiree
{
    return new Soiree(
        $id,
        $nom,
        'Rock',
        new DateTimeImmutable($date),
        DateTimeImmutable::createFromFormat('!H:i:s', $horaire),
        'Salle Poirel',
        600
    );
}

it('retourne un SoireeDTO par soirée', function () {
    expect($this->service->getAllSoirees())
        ->toHaveCount(3)
        ->each->toBeInstanceOf(SoireeDTO::class);
});

it('trie les soirées par date puis par horaire', function () {
    // soirées données dans le désordre : le tri doit venir du repository, pas de l'ordre d'insertion
    $service = serviceAvecSoirees([
        soireeLe('s3', 'Clôture', '2027-07-10', '21:00:00'),
        soireeLe('s2', 'Nuit Métal', '2027-07-09', '20:30:00'),
        soireeLe('s1b', 'Blues Night', '2027-07-08', '19:30:00'),
        soireeLe('s1a', 'Ouverture', '2027-07-08', '20:00:00'),
    ]);

    $noms = array_map(fn (SoireeDTO $s) => $s->nom, $service->getAllSoirees());

    expect($noms)->toBe(['Blues Night', 'Ouverture', 'Nuit Métal', 'Clôture']);
});

it("formate la date et l'horaire de chaque soirée", function () {
    $service = serviceAvecSoirees([soireeLe('s1', 'Ouverture', '2027-07-08', '20:00:00')]);

    [$soiree] = $service->getAllSoirees();

    expect($soiree->idSoiree)->toBe('s1')
        ->and($soiree->date)->toBe('2027-07-08')
        ->and($soiree->horaire)->toBe('20:00')
        ->and($soiree->lieu)->toBe('Salle Poirel');
});

it('produit le JSON attendu par la liste des soirées, sans le nombre de places', function () {
    $service = serviceAvecSoirees([soireeLe('s1', 'Ouverture', '2027-07-08', '20:00:00')]);

    $json = json_decode(json_encode($service->getAllSoirees()), true);

    expect($json[0])->toBe([
        'id' => 's1',
        'nom' => 'Ouverture',
        'thematique' => 'Rock',
        'date' => '2027-07-08',
        'horaire' => '20:00',
        'lieu' => 'Salle Poirel',
    ]);
});

it("retourne une liste vide quand il n'y a aucune soirée", function () {
    expect(serviceAvecSoirees([])->getAllSoirees())->toBe([]);
});
