<?php
declare(strict_types=1);


/*
 * Point d'entrée unique de l'API : toutes les requêtes passent par ce fichier
 * (php -S … -t public), puis Slim les dirige vers l'action de la route demandée.
 */

// autoload de Composer : classes du projet (PSR-4, namespace nrv\) et des bibliothèques
require_once __DIR__ . '/../vendor/autoload.php';

/* application bootstrap : configuration, conteneur, middlewares et routes */
$appli = require_once __DIR__ . '/../config/bootstrap.php';


$appli->run();