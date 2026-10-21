<?php

use nrv\adapters\controllers\actions\EnregistrerUtilisateurAction;
use nrv\application\usecases\UtilisateurService;
use nrv\application\validators\EnregistrerUtilisateurValidator;
use tests\pest\fakes\FakeUtilisateurRepository;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

function appelerEnregistrer(EnregistrerUtilisateurAction $action, mixed $corps): array
{
    $rq = (new ServerRequestFactory())->createServerRequest('POST', '/auth/register');
    if ($corps !== null) {
        $rq = $rq->withParsedBody($corps);
    }

    $rs = $action($rq, (new ResponseFactory())->createResponse(), []);

    return [$rs->getStatusCode(), json_decode((string) $rs->getBody(), true), $rs];
}

beforeEach(function () {
    $this->repo = new FakeUtilisateurRepository();
    $this->action = new EnregistrerUtilisateurAction(
        new UtilisateurService($this->repo, new EnregistrerUtilisateurValidator())
    );
    $this->corps = [
        'email' => 'jean@mail.fr',
        'nom' => 'Dupont',
        'prenom' => 'Jean',
        'password' => 'motdepasse123',
    ];
});

it('répond 201 avec l\'utilisateur créé et sans secret', function () {
    [$status, $json, $rs] = appelerEnregistrer($this->action, $this->corps);

    expect($status)->toBe(201)
        ->and($rs->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and($json['email'])->toBe('jean@mail.fr')
        ->and(array_keys($json))->toBe(['id', 'email', 'nom', 'prenom', 'role'])
        ->and((string) $rs->getBody())->not->toContain('motdepasse123');
});

it('répond 409 si l\'email existe déjà', function () {
    appelerEnregistrer($this->action, $this->corps);
    [$status, $json, $rs] = appelerEnregistrer($this->action, $this->corps);

    expect($status)->toBe(409)
        ->and($json['error'])->toBe('Cet email est déjà utilisé.')
        ->and($rs->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and($this->repo->utilisateurs)->toHaveCount(1);
});

it('répond 400 si le corps est absent', function () {
    [$status, $json] = appelerEnregistrer($this->action, null);

    expect($status)->toBe(400)->and($json)->toHaveKey('error');
});

it('répond 400 si le corps n\'est pas un tableau', function () {
    $obj = new stdClass();
    $obj->email = 'jean@mail.fr';

    [$status] = appelerEnregistrer($this->action, $obj);

    expect($status)->toBe(400)->and($this->repo->utilisateurs)->toBeEmpty();
});

it('répond 400 pour des données invalides et n\'enregistre rien', function (array $override) {
    [$status, $json] = appelerEnregistrer($this->action, array_merge($this->corps, $override));

    expect($status)->toBe(400)
        ->and($json)->toHaveKey('error')
        ->and($this->repo->utilisateurs)->toBeEmpty();
})->with([
            'email invalide' => [['email' => 'pas-un-email']],
            'email en tableau' => [['email' => ['a@b.fr']]],
            'nom manquant (null)' => [['nom' => null]],
            'mot de passe court' => [['password' => 'court']],
            'mot de passe numérique' => [['password' => 12345678]],
        ]);

it('répond 400 pour un corps vide ou sans champs', function () {
    [$statusVide] = appelerEnregistrer($this->action, []);
    [$statusInconnus] = appelerEnregistrer($this->action, ['foo' => 'bar']);

    expect($statusVide)->toBe(400)->and($statusInconnus)->toBe(400);
});

it('ignore un champ role envoyé par le client (pas d\'élévation de privilèges)', function () {
    [$status, $json] = appelerEnregistrer($this->action, array_merge($this->corps, ['role' => 99]));

    expect($status)->toBe(201)
        ->and($json['role'])->toBe(1)
        ->and($this->repo->utilisateurs[0]->getRole())->toBe(1);
});

it('ignore un champ id envoyé par le client', function () {
    [, $json] = appelerEnregistrer($this->action, array_merge($this->corps, ['id' => 'pirate']));

    expect($json['id'])->not->toBe('pirate');
});

it('laisse remonter les erreurs inattendues au middleware d\'erreurs (pas de 500 maison)', function () {
    $this->repo->saveThrows = new RuntimeException('base indisponible');

    expect(fn() => appelerEnregistrer($this->action, $this->corps))
        ->toThrow(RuntimeException::class);
});