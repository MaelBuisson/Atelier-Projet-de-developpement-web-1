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
use nrv\adapters\controllers\actions\EnregistrerAction;
use nrv\adapters\controllers\actions\ConnexionAction;

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
    $group->get('/{id}', GetSoireeDetailAction::class)
        ->setName('soiree-detail');
});

$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/register', EnregistrerAction::class)
        ->setName('register');
    $group->post('/signin', ConnexionAction::class)
        ->setName('signin');
});

// CORS : options pour les requêtes preflight
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

return $app;