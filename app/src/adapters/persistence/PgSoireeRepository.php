<?php

namespace nrv\adapters\persistence;

use DateTimeImmutable;
use nrv\application\ports\spi\SoireeRepository;
use nrv\domain\entities\Soiree;
use nrv\domain\exceptions\SoireeNotFound;
use PDO;

/**
 * Adaptateur de sortie : accès aux soirées stockées dans PostgreSQL.
 * Implémente le port SPI SoireeRepository défini dans la couche application.
 */
class PgSoireeRepository implements SoireeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retourne la soirée correspondant à l'identifiant (UUID).
     *
     * @throws SoireeNotFound si aucune soirée ne porte cet identifiant
     */
    public function findById(string $id): Soiree
    {
        // requête préparée : l'identifiant est passé en paramètre, ce qui protège des injections SQL
        $stmt = $this->pdo->prepare(
            'SELECT *
            FROM soiree
            WHERE id_soiree = :id'
        );

        $stmt->execute(['id' => $id]);

        $soireeData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($soireeData === false) {
            throw new SoireeNotFound($id);
        }

        return new Soiree(
            $soireeData['id_soiree'],
            $soireeData['nom'],
            $soireeData['thematique'],
            new DateTimeImmutable($soireeData['date']),
            DateTimeImmutable::createFromFormat('!H:i:s', $soireeData['horaire']),
            $soireeData['lieu'],
            (int) $soireeData['nb_place']
        );
    }
}
