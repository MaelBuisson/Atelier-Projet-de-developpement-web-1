<?php

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use nrv\adapters\config\ContainerConfig;
use nrv\adapters\controllers\middlewares\Cors;
use nrv\adapters\controllers\actions\ListerSpectaclesAction;

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();

// ── conteneur d'injection de dépendances ─
$container = ContainerConfig::build();
$app = AppFactory::createFromContainer($container);

// ── ajout middlewares ─
$app->addBodyParsingMiddleware();
$app->add(Cors::class);
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, false, false);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');
$errorHandler->registerErrorRenderer('application/json', function ($exception, $displayErrorDetails) {
    return json_encode(['message' => $exception->getMessage()]);
});

// ── ajout des routes ─
$app->group('/spectacles', function (RouteCollectorProxy $group) {
    $group->get('', ListerSpectaclesAction::class)
        ->setName('spectacles-list');
});

// CORS : options pour les requêtes preflight
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

return $app;