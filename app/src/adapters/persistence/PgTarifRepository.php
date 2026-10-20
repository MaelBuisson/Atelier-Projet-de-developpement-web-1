<?php

namespace nrv\adapters\persistence;

use nrv\application\ports\spi\TarifRepository;
use nrv\domain\entities\Tarif;
use Override;
use PDO;

/**
 * Adaptateur de sortie : accès aux tarifs stockés dans PostgreSQL.
 * Implémente le port SPI TarifRepository défini dans la couche application.
 */
class PgTarifRepository implements TarifRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retourne tous les tarifs du festival, du plus cher au moins cher.
     * Le prix (NUMERIC) arrive sous forme de chaîne depuis PDO : il est converti en float.
     *
     * @return Tarif[]
     */
    #[Override]
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
            FROM tarif
            ORDER BY prix DESC
            '
        );
        $stmt->execute();

        $tarifs = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tarifs[] = new Tarif($row['nom'], (float) $row['prix']);
        }

        return $tarifs;
    }
}