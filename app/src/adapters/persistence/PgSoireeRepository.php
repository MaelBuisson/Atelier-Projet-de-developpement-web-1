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

        return $this->toSoiree($soireeData);
    }

    /**
     * Retourne toutes les soirées, triées par date puis par horaire.
     *
     * @return Soiree[]
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
            FROM soiree
            ORDER BY date, horaire
            '
        );

        $stmt->execute();

        $soirees = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $soirees[] = $this->toSoiree($row);
        }

        return $soirees;
    }

    /**
     * Construit une entité Soiree à partir d'une ligne de la table soiree.
     * La colonne horaire (TIME) est lue avec '!H:i:s' : la date est fixée au 1970-01-01.
     */
    private function toSoiree(array $row): Soiree
    {
        return new Soiree(
            $row['id_soiree'],
            $row['nom'],
            $row['thematique'],
            new DateTimeImmutable($row['date']),
            DateTimeImmutable::createFromFormat('!H:i:s', $row['horaire']),
            $row['lieu'],
            (int) $row['nb_place']
        );
    }
}
