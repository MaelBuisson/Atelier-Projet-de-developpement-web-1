<?php

use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use nrv\adapters\config\ContainerConfig;
use nrv\adapters\controllers\middlewares\Cors;
use nrv\adapters\controllers\actions\ListerSpectaclesAction;
use nrv\adapters\controllers\actions\GetSoireeDetailAction;
use nrv\adapters\controllers\actions\EnregistrerUtilisateurAction;
use nrv\adapters\controllers\actions\ConnexionAction;
use nrv\adapters\controllers\actions\GetUtilisateurAction;
use nrv\adapters\controllers\actions\ListerSoireesAction;
use nrv\adapters\controllers\actions\GetPanierAction;
use nrv\adapters\controllers\actions\AjouterAuPanierAction;
use nrv\adapters\controllers\actions\SupprimerDuPanierAction;
use nrv\adapters\controllers\actions\ValiderPanierAction;
use nrv\adapters\controllers\actions\GetCommandeAction;
use nrv\adapters\controllers\actions\PayerCommandeAction;
use nrv\adapters\controllers\middlewares\AuthMiddleware;
use nrv\adapters\controllers\actions\GetBilletAction;
use nrv\adapters\controllers\actions\ListerCommandesAction;
use nrv\adapters\controllers\actions\GetBilletsCommandeAction;
use nrv\adapters\controllers\actions\GetJaugesAction;
use nrv\adapters\controllers\middlewares\AdminMiddleware;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();

// ── conteneur d'injection de dépendances ─
$container = ContainerConfig::build();
$app = AppFactory::createFromContainer($container);

// ── ajout middlewares ─
$app->addBodyParsingMiddleware();
$app->add(Cors::class);
$app->addRoutingMiddleware();
// détail des erreurs internes affiché uniquement si DISPLAY_ERROR_DETAILS=true dans config/.env (développement)
$displayErrorDetails = filter_var($_ENV['DISPLAY_ERROR_DETAILS'] ?? false, FILTER_VALIDATE_BOOL);
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, true, true);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');
$errorHandler->registerErrorRenderer('application/json', function ($exception, $displayErrorDetails) {
    // les erreurs HTTP de Slim (404, 405…) ont un message destiné au client ;
    // les autres (PDO, bug…) peuvent révéler l'infrastructure : message générique en production
    $visible = $exception instanceof HttpException || $displayErrorDetails;

    return json_encode(['message' => $visible ? $exception->getMessage() : 'Erreur interne du serveur']);
});

// ── ajout des routes ─
$app->group('/spectacles', function (RouteCollectorProxy $group) {
    $group->get('', ListerSpectaclesAction::class)
        ->setName('spectacles-list');
});

$app->group('/soirees', function (RouteCollectorProxy $group) {
    $group->get('', ListerSoireesAction::class)
        ->setName('soirees-list');
});

$app->group('/soiree', function (RouteCollectorProxy $group) {
    $group->get('/{id}', GetSoireeDetailAction::class)
        ->setName('soiree-detail');
});

$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/register', EnregistrerUtilisateurAction::class)
        ->setName('register');
    $group->post('/signin', ConnexionAction::class)
        ->setName('signin');
    // route protégée : le middleware est ajouté à cette route seulement,
    // register et signin restent accessibles sans token
    $group->get('/me', GetUtilisateurAction::class)
        ->setName('me')->add(AuthMiddleware::class);
});

// panier de l'utilisateur connecté : tout le groupe est protégé par le token
$app->group('/panier', function (RouteCollectorProxy $group) {
    $group->get('', GetPanierAction::class)
        ->setName('panier-get');
    $group->post('/lignes', AjouterAuPanierAction::class)
        ->setName('panier-ajouter');
    $group->delete('/lignes/{idLigne}', SupprimerDuPanierAction::class)
        ->setName('panier-supprimer');
    $group->post('/validation', ValiderPanierAction::class)
        ->setName('panier-valider');
})->add(AuthMiddleware::class);

// commandes de l'utilisateur connecté : tout le groupe est protégé par le token
$app->group('/commandes', function (RouteCollectorProxy $group) {
    $group->get('', ListerCommandesAction::class)
        ->setName('commandes-list');
    $group->get('/{id}', GetCommandeAction::class)
        ->setName('commande-detail');
    $group->post('/{id}/paiement', PayerCommandeAction::class)
        ->setName('commande-paiement');
    $group->get('/{id}/billets', GetBilletsCommandeAction::class)
        ->setName('commande-billets');
})->add(AuthMiddleware::class);

$app->get('/billets/{token}', GetBilletAction::class)->setName('get_billet')->add(AuthMiddleware::class);

$app->group('/backoffice', function (RouteCollectorProxy $group) {
    $group->get('/jauges', GetJaugesAction::class)
        ->setName('backoffice-jauges');
})->add(AdminMiddleware::class)->add(AuthMiddleware::class);
// CORS : options pour les requêtes preflight
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

return $app;