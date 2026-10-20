<?php

namespace nrv\adapters\persistence;

use nrv\application\ports\spi\SpectacleRepository;
class PgSpectacleRepository implements SpectacleRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, titre, horaire, soiree.date, 
            (Select url from image where idSpectacle = spectacle.id limit 1) as image
            FROM spectacle
            Join soiree ON spectacle.idSoiree = soiree.id"
        );
        $stmt->execute();
        $row = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $row;
    }

}