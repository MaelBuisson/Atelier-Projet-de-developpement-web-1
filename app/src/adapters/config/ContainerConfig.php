<?php

namespace nrv\adapters\config;

use DI\ContainerBuilder;
use nrv\adapters\persistence\PgSoireeRepository;
use Psr\Container\ContainerInterface;

use nrv\application\ports\api\SpectacleServiceInterface;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\application\usecases\SpectacleService;
use nrv\adapters\persistence\PgSpectacleRepository;
use nrv\adapters\persistence\PgTarifRepository;
use nrv\application\ports\api\SoireeServiceInterface;
use nrv\application\ports\spi\SoireeRepository;
use nrv\application\ports\spi\TarifRepository;
use nrv\application\usecases\SoireeService;
use nrv\application\ports\api\UtilisateurServiceInterface;
use nrv\application\ports\spi\UtilisateurRepository;
use nrv\application\usecases\UtilisateurService;
use nrv\adapters\persistence\PgUtilisateurRepository;
use nrv\application\ports\api\AuthServiceInterface;
use nrv\application\ports\spi\TokenProvider;
use nrv\application\usecases\AuthService;
use nrv\adapters\securite\JwtTokenProvider;
use nrv\adapters\controllers\middlewares\AuthMiddleware;
use nrv\application\validators\EnregistrerUtilisateurValidator;
use nrv\application\ports\api\CommandeServiceInterface;
use nrv\application\ports\spi\CommandeRepository;
use nrv\application\usecases\CommandeService;
use nrv\adapters\persistence\PgCommandeRepository;
use nrv\application\ports\api\BilletServiceInterface;
use nrv\application\ports\spi\BilletRepository;
use nrv\application\usecases\BilletService;
use nrv\adapters\persistence\PgBilletRepository;
/**
 * Configuration du conteneur PHP-DI : seul endroit du code qui associe chaque port
 * (interface) à son implémentation. Les actions et les services ne reçoivent que des interfaces.
 */
final class ContainerConfig
{
    public static function build(): \DI\Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([

                // ── domain services ─
            SpectacleServiceInterface::class => \DI\create(SpectacleService::class)
                ->constructor(\DI\get(SpectacleRepository::class)),

                // le service des soirées reçoit les trois ports SPI dont il a besoin (pas le PDO)
            SoireeServiceInterface::class => \DI\create(SoireeService::class)
                ->constructor(
                    \DI\get(SoireeRepository::class),
                    \DI\get(SpectacleRepository::class),
                    \DI\get(TarifRepository::class)
                ),

            UtilisateurServiceInterface::class => \DI\create(UtilisateurService::class)
                ->constructor(\DI\get(UtilisateurRepository::class), \DI\create(EnregistrerUtilisateurValidator::class)),

            AuthServiceInterface::class => \DI\create(AuthService::class)
                ->constructor(\DI\get(UtilisateurRepository::class), \DI\get(TokenProvider::class)),

            CommandeServiceInterface::class => \DI\create(CommandeService::class)
                ->constructor(\DI\get(CommandeRepository::class), \DI\get(UtilisateurRepository::class), \DI\get(SoireeRepository::class), \DI\get(TarifRepository::class)),

            AuthMiddleware::class => function (ContainerInterface $c) {
                return new AuthMiddleware($_ENV['JWT_SECRET'], new \Slim\Psr7\Factory\ResponseFactory());
            },

            TokenProvider::class => function () {
                return new JwtTokenProvider($_ENV['JWT_SECRET']);
            },
            BilletServiceInterface::class => \DI\create(BilletService::class)
                ->constructor(\DI\get(BilletRepository::class)),
            // ── adapters ─
            // connexion PDO unique, partagée par tous les repositories ; paramètres lus dans config/.env
            // ERRMODE_EXCEPTION : une erreur SQL lève une exception au lieu de renvoyer false
            'nrv.pdo' => function (ContainerInterface $c) {
                $dsn = "{$_ENV['DRIVER']}:host={$_ENV['HOST']};dbname={$_ENV['DATABASE']}";
                $user = $_ENV['USERNAME'];
                $password = $_ENV['PASSWORD'];
                return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            },

            SpectacleRepository::class => \DI\create(PgSpectacleRepository::class)
                ->constructor(\DI\get('nrv.pdo')),

            SoireeRepository::class => \DI\create(PgSoireeRepository::class)
                ->constructor(\DI\get('nrv.pdo')),

            TarifRepository::class => \DI\create(PgTarifRepository::class)
                ->constructor(\DI\get('nrv.pdo')),
            UtilisateurRepository::class => \DI\create(PgUtilisateurRepository::class)
                ->constructor(\DI\get('nrv.pdo')),
            CommandeRepository::class => \DI\create(PgCommandeRepository::class)
                ->constructor(\DI\get('nrv.pdo')),
            BilletRepository::class => \DI\create(PgBilletRepository::class)
                ->constructor(\DI\get('nrv.pdo')),
        ]);
        return $builder->build();
    }
}