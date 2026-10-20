<?php

namespace nrv\adapters\config;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

use nrv\application\ports\api\SpectacleServiceInterface;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\application\usecases\SpectacleService;
use nrv\adapters\persistence\PgSpectacleRepository;

final class ContainerConfig
{
    public static function build(): \DI\Container
    {
        $builder = new ContainerBuilder();
        $builder->addDefinitions([

                // ── domain services ─
            SpectacleServiceInterface::class => \DI\create(SpectacleService::class)
                ->constructor(\DI\get(SpectacleRepository::class)),

            // ── adapters ─
            'nrv.pdo' => function (ContainerInterface $c) {
                $dsn = "{$_ENV['DRIVER']}:host={$_ENV['HOST']};dbname={$_ENV['DATABASE']}";
                $user = $_ENV['USERNAME'];
                $password = $_ENV['PASSWORD'];
                return new \PDO($dsn, $user, $password, [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]);
            },

            SpectacleRepository::class => \DI\create(PgSpectacleRepository::class)
                ->constructor(\DI\get('nrv.pdo')),
        ]);
        return $builder->build();
    }
}