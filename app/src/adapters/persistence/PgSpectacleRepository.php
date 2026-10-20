<?php

namespace nrv\adapters\persistence;

use DateTime;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\domain\entities\Image;
use nrv\domain\entities\Soiree;
use nrv\domain\entities\Spectacle;

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
            "SELECT spectacle.id, spectacle.titre, spectacle.horaire,
                    soiree.id AS soiree_id, soiree.nom AS soiree_nom, soiree.date AS soiree_date,
                    img.id AS image_id, img.url AS image_url
             FROM spectacle
             JOIN soiree ON spectacle.idSoiree = soiree.id
             LEFT JOIN LATERAL (
                 SELECT id, url FROM image
                 WHERE image.idSpectacle = spectacle.id
                 ORDER BY id LIMIT 1
             ) img ON true"
        );
        $stmt->execute();

        $spectacles = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $soiree = new Soiree(
                $row['soiree_id'],
                new DateTime($row['soiree_date']),
                $row['soiree_nom']
            );

            $spectacle = new Spectacle(
                $row['id'],
                $row['titre'],
                new DateTime($row['horaire'])
            );
            $spectacle->assignSoiree($soiree);

            if ($row['image_id'] !== null) {
                $spectacle->addImages([new Image($row['image_id'], $row['image_url'])]);
            }

            $spectacles[] = $spectacle;
        }

        return $spectacles;
    }
}