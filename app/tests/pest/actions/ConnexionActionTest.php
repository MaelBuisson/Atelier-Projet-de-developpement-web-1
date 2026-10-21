<?php

use nrv\adapters\controllers\actions\ConnexionAction;
use nrv\application\usecases\AuthService;
use nrv\domain\entities\Utilisateur;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use tests\pest\fakes\FakeTokenProvider;
use tests\pest\fakes\FakeUtilisateurRepository;

function appelerConnexion(ConnexionAction $action, mixed $corps): array
{
    $rq = (new ServerRequestFactory())->createServerRequest('POST', '/auth/signin');
    if ($corps !== null) {
        $rq = $rq->withParsedBody($corps);
    }

    $rs = $action($rq, (new ResponseFactory())->createResponse(), []);

    return [$rs->getStatusCode(), json_decode((string) $rs->getBody(), true), $rs];
}

beforeEach(function () {
    $this->repo = new FakeUtilisateurRepository();
    $this->repo->save(new Utilisateur(
        'id-1',
        'jean@mail.fr',
        'Dupont',
        'Jean',
        password_hash('motdepasse123', PASSWORD_BCRYPT, ['cost' => 4])
    ));
    $this->action = new ConnexionAction(new AuthService($this->repo, new FakeTokenProvider()));
    $this->corps = ['email' => 'jean@mail.fr', 'password' => 'motdepasse123'];
});

it('répond 200 avec le token et l\'utilisateur', function () {
    [$status, $json, $rs] = appelerConnexion($this->action, $this->corps);

    expect($status)->toBe(200)
        ->and($rs->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and($json['token'])->toBe('token-id-1')
        ->and($json['utilisateur']['email'])->toBe('jean@mail.fr')
        ->and(array_keys($json))->toBe(['token', 'utilisateur'])
        ->and((string) $rs->getBody())->not->toContain('motdepasse123')
        ->and((string) $rs->getBody())->not->toContain('$2y$');
});

it('répond 401 pour un mauvais mot de passe', function () {
    [$status, $json, $rs] = appelerConnexion($this->action, array_merge($this->corps, ['password' => 'faux']));

    expect($status)->toBe(401)
        ->and($json['error'])->toBe('Email ou mot de passe incorrect.')
        ->and($rs->getHeaderLine('Content-Type'))->toContain('application/json')
        ->and($json)->not->toHaveKey('token');
});

it('répond 401 pour un email inconnu', function () {
    [$status, $json] = appelerConnexion($this->action, array_merge($this->corps, ['email' => 'inconnu@mail.fr']));

    expect($status)->toBe(401)->and($json['error'])->toBe('Email ou mot de passe incorrect.');
});

it('répond de la même façon pour un email inconnu et un mauvais mot de passe', function () {
    [$statusA, $jsonA] = appelerConnexion($this->action, array_merge($this->corps, ['email' => 'inconnu@mail.fr']));
    [$statusB, $jsonB] = appelerConnexion($this->action, array_merge($this->corps, ['password' => 'faux']));

    expect($statusA)->toBe($statusB)->and($jsonA)->toBe($jsonB);
});

it('répond 400 si le corps est absent', function () {
    [$status, $json] = appelerConnexion($this->action, null);

    expect($status)->toBe(400)->and($json)->toHaveKey('error');
});

it('répond 400 si le corps n\'est pas un tableau', function () {
    $obj = new stdClass();
    $obj->email = 'jean@mail.fr';

    [$status] = appelerConnexion($this->action, $obj);

    expect($status)->toBe(400);
});

it('répond 400 pour des champs vides, absents ou mal typés', function (array $corps) {
    [$status, $json] = appelerConnexion($this->action, $corps);

    expect($status)->toBe(400)->and($json)->toHaveKey('error');
})->with([
            'corps vide' => [[]],
            'email vide' => [['email' => '', 'password' => 'motdepasse123']],
            'mot de passe vide' => [['email' => 'jean@mail.fr', 'password' => '']],
            'email manquant' => [['password' => 'motdepasse123']],
            'mot de passe manquant' => [['email' => 'jean@mail.fr']],
            'email en tableau' => [['email' => ['jean@mail.fr'], 'password' => 'motdepasse123']],
            'mot de passe numérique' => [['email' => 'jean@mail.fr', 'password' => 12345678]],
            'champs inconnus seulement' => [['foo' => 'bar']],
        ]);

it('répond 401 pour une tentative d\'injection SQL', function () {
    [$status] = appelerConnexion($this->action, ['email' => "' OR '1'='1", 'password' => "' OR '1'='1"]);

    expect($status)->toBe(401);
});

it('ignore un champ role envoyé par le client', function () {
    [$status, $json] = appelerConnexion($this->action, array_merge($this->corps, ['role' => 99]));

    expect($status)->toBe(200)->and($json['utilisateur']['role'])->toBe(1);
});

it('laisse remonter les erreurs inattendues au middleware d\'erreurs', function () {
    $provider = new class implements \nrv\application\ports\spi\TokenProvider {
        public function generer(Utilisateur $utilisateur): string
        {
            throw new RuntimeException('clé indisponible');
        }
    };
    $action = new ConnexionAction(new AuthService($this->repo, $provider));

    expect(fn() => appelerConnexion($action, $this->corps))->toThrow(RuntimeException::class);
});