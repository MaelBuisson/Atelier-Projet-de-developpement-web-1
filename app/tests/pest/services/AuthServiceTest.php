<?php

use nrv\application\dto\ConnexionDTO;
use nrv\application\exceptions\MauvaisMdpOuEmailException;
use nrv\application\exceptions\ValidationException;
use nrv\application\usecases\AuthService;
use nrv\domain\entities\Utilisateur;
use tests\pest\fakes\FakeTokenProvider;
use tests\pest\fakes\FakeUtilisateurRepository;

function dtoConnexion(string $email, string $password): ConnexionDTO
{
    return ConnexionDTO::fromArray(['email' => $email, 'password' => $password]);
}

beforeEach(function () {
    $this->repo = new FakeUtilisateurRepository();
    $this->repo->save(new Utilisateur(
        'id-1',
        'jean@mail.fr',
        'Dupont',
        'Jean',
        password_hash('motdepasse123', PASSWORD_BCRYPT, ['cost' => 4]),
        1
    ));
    $this->repo->save(new Utilisateur(
        'id-2',
        'admin@mail.fr',
        'Admin',
        'Alice',
        password_hash('adminpass456', PASSWORD_BCRYPT, ['cost' => 4]),
        2
    ));
    $this->repo->saveCalls = 0;
    $this->repo->emailsCherches = [];
    $this->service = new AuthService($this->repo, new FakeTokenProvider());
});

// ── nominal ─

it('connecte un utilisateur avec les bons identifiants', function () {
    $auth = $this->service->connecter(dtoConnexion('jean@mail.fr', 'motdepasse123'));

    expect($auth->token)->toBe('token-id-1')
        ->and($auth->utilisateur->id)->toBe('id-1')
        ->and($auth->utilisateur->email)->toBe('jean@mail.fr')
        ->and($auth->utilisateur->nom)->toBe('Dupont')
        ->and($auth->utilisateur->prenom)->toBe('Jean')
        ->and($auth->utilisateur->role)->toBe(1);
});

it('connecte le bon utilisateur quand il y en a plusieurs', function () {
    $auth = $this->service->connecter(dtoConnexion('admin@mail.fr', 'adminpass456'));

    expect($auth->utilisateur->id)->toBe('id-2')->and($auth->utilisateur->role)->toBe(2);
});

it('ne mélange pas les mots de passe de deux comptes', function () {
    expect(fn() => $this->service->connecter(dtoConnexion('jean@mail.fr', 'adminpass456')))
        ->toThrow(MauvaisMdpOuEmailException::class);
});

it('ignore la casse et les espaces autour de l\'email', function (string $email) {
    $auth = $this->service->connecter(dtoConnexion($email, 'motdepasse123'));

    expect($auth->utilisateur->id)->toBe('id-1');
})->with([
            'majuscules' => 'JEAN@MAIL.FR',
            'casse mixte' => 'Jean@Mail.Fr',
            'espaces autour' => '  jean@mail.fr  ',
        ]);

it('cherche l\'email en minuscules dans le repository', function () {
    $this->service->connecter(dtoConnexion('JEAN@MAIL.FR', 'motdepasse123'));

    expect($this->repo->emailsCherches)->toBe(['jean@mail.fr']);
});

it('n\'écrit rien en base lors d\'une connexion', function () {
    $this->service->connecter(dtoConnexion('jean@mail.fr', 'motdepasse123'));

    expect($this->repo->saveCalls)->toBe(0)->and($this->repo->utilisateurs)->toHaveCount(2);
});

// ── identifiants incorrects ─

it('refuse un mauvais mot de passe', function (string $password) {
    expect(fn() => $this->service->connecter(dtoConnexion('jean@mail.fr', $password)))
        ->toThrow(MauvaisMdpOuEmailException::class, 'Email ou mot de passe incorrect.');
})->with([
            'faux' => 'autremotdepasse',
            'casse différente' => 'MOTDEPASSE123',
            'espace en plus à la fin' => 'motdepasse123 ',
            'espace en plus au début' => ' motdepasse123',
            'un caractère de moins' => 'motdepasse12',
            'un caractère de plus' => 'motdepasse1234',
            'le hash lui-même' => '$2y$04$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012',
        ]);

it('refuse un email inconnu', function () {
    expect(fn() => $this->service->connecter(dtoConnexion('inconnu@mail.fr', 'motdepasse123')))
        ->toThrow(MauvaisMdpOuEmailException::class, 'Email ou mot de passe incorrect.');
});

it('donne le même message pour un email inconnu et un mauvais mot de passe', function () {
    $messages = [];
    foreach ([['inconnu@mail.fr', 'motdepasse123'], ['jean@mail.fr', 'fauxfaux']] as [$email, $password]) {
        try {
            $this->service->connecter(dtoConnexion($email, $password));
        } catch (MauvaisMdpOuEmailException $e) {
            $messages[] = $e->getMessage();
        }
    }

    expect($messages)->toHaveCount(2)->and($messages[0])->toBe($messages[1]);
});

it('ne génère aucun token en cas d\'échec', function () {
    $provider = new class implements \nrv\application\ports\spi\TokenProvider {
        public int $appels = 0;
        public function generer(Utilisateur $utilisateur): string
        {
            $this->appels++;
            return 'token';
        }
    };
    $service = new AuthService($this->repo, $provider);

    try {
        $service->connecter(dtoConnexion('jean@mail.fr', 'faux-mot-de-passe'));
    } catch (MauvaisMdpOuEmailException) {
    }

    expect($provider->appels)->toBe(0);
});

it('ne se laisse pas piéger par une injection dans l\'email ou le mot de passe', function (string $email, string $password) {
    expect(fn() => $this->service->connecter(dtoConnexion($email, $password)))
        ->toThrow(MauvaisMdpOuEmailException::class);
})->with([
            'tautologie dans l\'email' => ["' OR '1'='1", 'motdepasse123'],
            'commentaire sql' => ["jean@mail.fr' --", 'motdepasse123'],
            'tautologie dans le mot de passe' => ['jean@mail.fr', "' OR '1'='1"],
            'jokers' => ['%', 'motdepasse123'],
        ]);

// ── validation ─

it('refuse les champs vides sans interroger le repository', function (string $email, string $password) {
    expect(fn() => $this->service->connecter(dtoConnexion($email, $password)))
        ->toThrow(ValidationException::class);

    expect($this->repo->emailsCherches)->toBeEmpty();
})->with([
            'email vide' => ['', 'motdepasse123'],
            'mot de passe vide' => ['jean@mail.fr', ''],
            'les deux vides' => ['', ''],
            'email en espaces seulement' => ['   ', 'motdepasse123'],
        ]);

it('n\'applique pas les règles d\'inscription à la connexion', function () {
    // Un ancien mot de passe de 3 caractères doit donner "identifiants invalides", pas une erreur de validation
    expect(fn() => $this->service->connecter(dtoConnexion('jean@mail.fr', 'abc')))
        ->toThrow(MauvaisMdpOuEmailException::class);
});

// ── sortie ─

it('ne renvoie jamais le mot de passe ni le hash', function () {
    $json = json_encode($this->service->connecter(dtoConnexion('jean@mail.fr', 'motdepasse123')));

    expect($json)->not->toContain('motdepasse123')
        ->and($json)->not->toContain($this->repo->utilisateurs[0]->getPasswordHash())
        ->and(array_keys(json_decode($json, true)))->toBe(['token', 'utilisateur'])
        ->and(array_keys(json_decode($json, true)['utilisateur']))->toBe(['id', 'email', 'nom', 'prenom', 'role']);
});

it('permet plusieurs connexions successives du même utilisateur', function () {
    foreach (range(1, 5) as $i) {
        $auth = $this->service->connecter(dtoConnexion('jean@mail.fr', 'motdepasse123'));
        expect($auth->utilisateur->id)->toBe('id-1');
    }
});

it('ne masque pas une erreur technique du token provider', function () {
    $provider = new class implements \nrv\application\ports\spi\TokenProvider {
        public function generer(Utilisateur $utilisateur): string
        {
            throw new RuntimeException('clé indisponible');
        }
    };

    expect(fn() => (new AuthService($this->repo, $provider))
        ->connecter(dtoConnexion('jean@mail.fr', 'motdepasse123')))
        ->toThrow(RuntimeException::class, 'clé indisponible');
});