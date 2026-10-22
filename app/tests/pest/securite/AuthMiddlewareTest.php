<?php

/*
 * Tests du middleware AuthMiddleware : seules les requêtes avec un token JWT valide
 * (header « Authorization: Bearer <token> ») atteignent l'action, avec l'identité de l'utilisateur.
 */

use Firebase\JWT\JWT;
use nrv\adapters\controllers\middlewares\AuthMiddleware;
use nrv\adapters\securite\JwtTokenProvider;
use nrv\domain\entities\Utilisateur;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

// nom différent de SECRET_TEST (JwtTokenProviderTest) : les constantes sont globales à toute la suite
const SECRET_MIDDLEWARE = 'secret_du_middleware_de_test_bien_plus_long_que_32_caracteres';

/**
 * Faux handler (la suite de la chaîne, en pratique l'action) : il mémorise la requête reçue
 * pour vérifier ce que le middleware a transmis, et répond 200.
 */
function handlerQuiMemorise(): RequestHandlerInterface
{
    return new class implements RequestHandlerInterface {
        public ?ServerRequestInterface $requete = null;

        public function handle(ServerRequestInterface $request): ResponseInterface
        {
            $this->requete = $request;
            return (new ResponseFactory())->createResponse(200);
        }
    };
}

/**
 * Passe une requête GET /auth/me dans le middleware, avec la valeur de header Authorization donnée.
 *
 * @return array{0: ResponseInterface, 1: RequestHandlerInterface} la réponse et le handler
 */
function passerMiddleware(?string $authorization): array
{
    $requete = (new ServerRequestFactory())->createServerRequest('GET', '/auth/me');
    if ($authorization !== null) {
        $requete = $requete->withHeader('Authorization', $authorization);
    }

    $handler = handlerQuiMemorise();
    $middleware = new AuthMiddleware(SECRET_MIDDLEWARE, new ResponseFactory());

    return [$middleware($requete, $handler), $handler];
}

function tokenValide(int $role = Utilisateur::ROLE_CLIENT): string
{
    $utilisateur = new Utilisateur('id-utilisateur-42', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', $role);

    return (new JwtTokenProvider(SECRET_MIDDLEWARE))->generer($utilisateur);
}

// ── accès autorisé ─

it("laisse passer un token valide et transmet l'identité à l'action", function () {
    [$reponse, $handler] = passerMiddleware('Bearer ' . tokenValide());

    expect($reponse->getStatusCode())->toBe(200)
        ->and($handler->requete->getAttribute('userId'))->toBe('id-utilisateur-42')
        ->and($handler->requete->getAttribute('role'))->toBe(Utilisateur::ROLE_CLIENT);
});

it('transmet le rôle contenu dans le token', function () {
    [, $handler] = passerMiddleware('Bearer ' . tokenValide(role: 2));

    expect($handler->requete->getAttribute('role'))->toBe(2);
});

// ── accès refusé : la requête n'atteint jamais l'action ─

it('refuse une requête sans header Authorization', function () {
    [$reponse, $handler] = passerMiddleware(null);

    expect($reponse->getStatusCode())->toBe(401)
        ->and(json_decode((string) $reponse->getBody(), true))->toBe(['error' => 'Token manquant.'])
        ->and($handler->requete)->toBeNull();
});

it('refuse un header Authorization mal formé', function (string $header) {
    [$reponse, $handler] = passerMiddleware($header);

    expect($reponse->getStatusCode())->toBe(401)
        ->and($handler->requete)->toBeNull();
})->with([
    'token sans Bearer' => fn () => tokenValide(),
    'Bearer sans token' => 'Bearer ',
    'autre schéma' => fn () => 'Basic ' . tokenValide(),
    'vide' => '',
]);

it('refuse un token signé avec un autre secret', function () {
    $faux = JWT::encode(
        ['sub' => 'id-utilisateur-42', 'role' => 1, 'exp' => time() + 600],
        'un_autre_secret_qui_fait_lui_aussi_plus_de_32_caracteres',
        'HS256'
    );

    [$reponse, $handler] = passerMiddleware("Bearer $faux");

    expect($reponse->getStatusCode())->toBe(401)
        ->and(json_decode((string) $reponse->getBody(), true))->toBe(['error' => 'Token invalide ou expiré.'])
        ->and($handler->requete)->toBeNull();
});

it('refuse un token expiré', function () {
    // durée négative : le token est déjà expiré au moment de sa création
    $utilisateur = new Utilisateur('id-utilisateur-42', 'jean@mail.fr', 'Dupont', 'Jean', 'hash', 1);
    $expire = (new JwtTokenProvider(SECRET_MIDDLEWARE, -60))->generer($utilisateur);

    [$reponse, $handler] = passerMiddleware("Bearer $expire");

    expect($reponse->getStatusCode())->toBe(401)
        ->and($handler->requete)->toBeNull();
});

it('refuse un token modifié après sa signature', function () {
    // on remplace le contenu (2e partie) pour se faire passer pour un autre utilisateur
    [$entete, , $signature] = explode('.', tokenValide());
    $contenu = rtrim(strtr(base64_encode(json_encode(['sub' => 'autre-id', 'role' => 2, 'exp' => time() + 600])), '+/', '-_'), '=');

    [$reponse, $handler] = passerMiddleware("Bearer $entete.$contenu.$signature");

    expect($reponse->getStatusCode())->toBe(401)
        ->and($handler->requete)->toBeNull();
});

it("refuse un token qui n'est pas un JWT", function () {
    [$reponse, $handler] = passerMiddleware('Bearer nimporte-quoi');

    expect($reponse->getStatusCode())->toBe(401)
        ->and($handler->requete)->toBeNull();
});

it('répond en JSON quand il refuse une requête', function () {
    [$reponse] = passerMiddleware(null);

    expect($reponse->getHeaderLine('Content-Type'))->toContain('application/json');
});
