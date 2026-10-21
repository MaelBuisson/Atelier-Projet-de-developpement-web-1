<?php

use nrv\application\dto\EnregistrerUtilisateurDTO;
use nrv\application\exceptions\EmailDejaUtiliseException;
use nrv\application\exceptions\ValidationException;
use nrv\application\usecases\UtilisateurService;
use nrv\application\validators\EnregistrerUtilisateurValidator;
use nrv\domain\entities\Utilisateur;
use tests\pest\fakes\FakeUtilisateurRepository;

function dtoPourService(array $o = []): EnregistrerUtilisateurDTO
{
    return EnregistrerUtilisateurDTO::fromArray(array_merge([
        'email' => 'jean.dupont@mail.fr',
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'password' => 'motdepasse123',
    ], $o));
}

beforeEach(function () {
    $this->repo = new FakeUtilisateurRepository();
    $this->service = new UtilisateurService($this->repo, new EnregistrerUtilisateurValidator());
});

// ── nominal ─

it('enregistre un utilisateur et renvoie son DTO', function () {
    $sortie = $this->service->enregistrer(dtoPourService());

    expect($this->repo->utilisateurs)->toHaveCount(1)
        ->and($this->repo->saveCalls)->toBe(1)
        ->and($sortie->email)->toBe('jean.dupont@mail.fr')
        ->and($sortie->nom)->toBe('Dupont')
        ->and($sortie->prenom)->toBe('Jean')
        ->and($sortie->role)->toBe(Utilisateur::ROLE_CLIENT);
});

it('persiste la même entité que celle renvoyée', function () {
    $sortie = $this->service->enregistrer(dtoPourService());
    $stocke = $this->repo->utilisateurs[0];

    expect($stocke->getId())->toBe($sortie->id)
        ->and($stocke->getEmail())->toBe($sortie->email)
        ->and($stocke->getRole())->toBe(Utilisateur::ROLE_CLIENT);
});

it('met l\'email en minuscules avant de le stocker et de le chercher', function () {
    $sortie = $this->service->enregistrer(dtoPourService(['email' => 'Jean.Dupont@MAIL.FR']));

    expect($sortie->email)->toBe('jean.dupont@mail.fr')
        ->and($this->repo->utilisateurs[0]->getEmail())->toBe('jean.dupont@mail.fr')
        ->and($this->repo->emailsCherches)->toBe(['jean.dupont@mail.fr']);
});

it('ne modifie pas la casse du nom et du prénom', function () {
    $sortie = $this->service->enregistrer(dtoPourService(['nom' => 'DuPont', 'prenom' => 'jEAN']));

    expect($sortie->nom)->toBe('DuPont')->and($sortie->prenom)->toBe('jEAN');
});

it('génère un UUID v4', function () {
    $sortie = $this->service->enregistrer(dtoPourService());

    expect($sortie->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
});

it('donne des identifiants différents à chaque utilisateur', function () {
    $ids = [];
    foreach (range(1, 20) as $i) {
        $ids[] = $this->service->enregistrer(dtoPourService(['email' => "u$i@mail.fr"]))->id;
    }

    expect(array_unique($ids))->toHaveCount(20);
});

// ── mot de passe ─

it('ne stocke jamais le mot de passe en clair', function () {
    $this->service->enregistrer(dtoPourService(['password' => 'motdepasse123']));
    $u = $this->repo->utilisateurs[0];

    expect($u->getPasswordHash())->not->toBe('motdepasse123')
        ->and($u->getPasswordHash())->not->toContain('motdepasse123')
        ->and(serialize($u))->not->toContain('motdepasse123');
});

it('stocke un hash vérifiable avec password_verify', function () {
    $this->service->enregistrer(dtoPourService(['password' => 'MotDePasse123']));
    $hash = $this->repo->utilisateurs[0]->getPasswordHash();

    expect(password_verify('MotDePasse123', $hash))->toBeTrue()
        ->and(password_verify('motdepasse123', $hash))->toBeFalse()
        ->and(password_verify('MotDePasse12', $hash))->toBeFalse()
        ->and(password_verify('MotDePasse123 ', $hash))->toBeFalse()
        ->and(password_verify('', $hash))->toBeFalse();
});

it('gère un mot de passe avec accents et symboles', function () {
    $this->service->enregistrer(dtoPourService(['password' => 'Mdp-é€-12345']));

    expect(password_verify('Mdp-é€-12345', $this->repo->utilisateurs[0]->getPasswordHash()))->toBeTrue();
});

it('utilise un algorithme de hash reconnu et tient dans VARCHAR(255)', function () {
    $this->service->enregistrer(dtoPourService());
    $hash = $this->repo->utilisateurs[0]->getPasswordHash();

    expect(password_get_info($hash)['algoName'])->not->toBe('unknown')
        ->and(strlen($hash))->toBeLessThanOrEqual(255);
});

it('produit des hashs différents pour le même mot de passe (sel)', function () {
    $this->service->enregistrer(dtoPourService(['email' => 'a@mail.fr']));
    $this->service->enregistrer(dtoPourService(['email' => 'b@mail.fr']));

    expect($this->repo->utilisateurs[0]->getPasswordHash())
        ->not->toBe($this->repo->utilisateurs[1]->getPasswordHash());
});

it('ne renvoie ni mot de passe ni hash dans la sortie', function () {
    $sortie = $this->service->enregistrer(dtoPourService(['password' => 'motdepasse123']));
    $json = json_encode($sortie);

    expect(array_keys(json_decode($json, true)))->toBe(['id', 'email', 'nom', 'prenom', 'role'])
        ->and($json)->not->toContain('motdepasse123')
        ->and($json)->not->toContain($this->repo->utilisateurs[0]->getPasswordHash());
});

// ── doublons ─

it('refuse un email déjà utilisé', function () {
    $this->service->enregistrer(dtoPourService());

    expect(fn() => $this->service->enregistrer(dtoPourService(['nom' => 'Autre'])))
        ->toThrow(EmailDejaUtiliseException::class, 'Cet email est déjà utilisé.');
});

it('n\'enregistre rien en cas de doublon', function () {
    $this->service->enregistrer(dtoPourService());

    try {
        $this->service->enregistrer(dtoPourService(['nom' => 'Autre', 'password' => 'autremotdepasse']));
    } catch (EmailDejaUtiliseException) {
    }

    expect($this->repo->utilisateurs)->toHaveCount(1)
        ->and($this->repo->saveCalls)->toBe(1)
        ->and($this->repo->utilisateurs[0]->getNom())->toBe('Dupont');
});

it('détecte le doublon malgré la casse ou les espaces autour', function (string $variante) {
    $this->service->enregistrer(dtoPourService(['email' => 'jean@mail.fr']));

    expect(fn() => $this->service->enregistrer(dtoPourService(['email' => $variante])))
        ->toThrow(EmailDejaUtiliseException::class);
})->with([
            'majuscules' => 'JEAN@MAIL.FR',
            'casse mixte' => 'Jean@Mail.Fr',
            'espaces autour' => '  jean@mail.fr  ',
        ]);

it('accepte des emails différents, y compris avec un tag +', function () {
    $this->service->enregistrer(dtoPourService(['email' => 'jean@mail.fr']));
    $this->service->enregistrer(dtoPourService(['email' => 'jean+nrv@mail.fr']));
    $this->service->enregistrer(dtoPourService(['email' => 'jean@mail.com']));

    expect($this->repo->utilisateurs)->toHaveCount(3);
});

// ── validation ─

it('refuse les données invalides sans toucher au repository', function (array $override) {
    expect(fn() => $this->service->enregistrer(dtoPourService($override)))
        ->toThrow(ValidationException::class);

    expect($this->repo->emailsCherches)->toBeEmpty()
        ->and($this->repo->saveCalls)->toBe(0)
        ->and($this->repo->utilisateurs)->toBeEmpty();
})->with([
            'email invalide' => [['email' => 'pas-un-email']],
            'email vide' => [['email' => '']],
            'nom vide' => [['nom' => '']],
            'prénom vide' => [['prenom' => '']],
            'nom avec espaces seulement' => [['nom' => '   ']],
            'mot de passe court' => [['password' => 'court']],
            'mot de passe vide' => [['password' => '']],
        ]);

it('ne fait pas confiance au DTO : un DTO construit à la main est validé aussi', function () {
    $dto = new EnregistrerUtilisateurDTO(' jean@mail.fr', 'Dupont', 'Jean', 'motdepasse123');

    expect(fn() => $this->service->enregistrer($dto))->toThrow(ValidationException::class);
    expect($this->repo->utilisateurs)->toBeEmpty();
});

it('refuse un email contenant une charge d\'injection SQL sans rien enregistrer', function () {
    expect(fn() => $this->service->enregistrer(dtoPourService([
        'email' => "x'; DROP TABLE utilisateur;--@mail.fr",
    ])))->toThrow(ValidationException::class);

    expect($this->repo->saveCalls)->toBe(0);
});

// ── erreurs techniques ─

it('ne masque pas une erreur du repository', function () {
    $this->repo->saveThrows = new RuntimeException('base indisponible');

    expect(fn() => $this->service->enregistrer(dtoPourService()))
        ->toThrow(RuntimeException::class, 'base indisponible');
});

it('peut enregistrer beaucoup d\'utilisateurs de suite', function () {
    foreach (range(1, 50) as $i) {
        $this->service->enregistrer(dtoPourService(['email' => "user$i@mail.fr"]));
    }

    expect($this->repo->utilisateurs)->toHaveCount(50);
});