<?php

namespace nrv\adapters\persistence;

use nrv\application\ports\spi\TarifRepository;
use nrv\domain\entities\Tarif;
use nrv\domain\exceptions\TarifNotFound;
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
     * Retourne les tarifs de la soirée (normal, réduit), du plus cher au moins cher.
     * Le prix (NUMERIC) arrive sous forme de chaîne depuis PDO : il est converti en float.
     *
     * @return Tarif[]
     */
    public function findBySoiree(string $idSoiree): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT nom, prix
            FROM tarif
            WHERE id_soiree = :id
            ORDER BY prix DESC
            '
        );
        $stmt->execute(['id' => $idSoiree]);

        $tarifs = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tarifs[] = new Tarif($row['nom'], (float) $row['prix']);
        }

        return $tarifs;
    }

    /**
     * Le tarif de la soirée portant ce nom : la clé primaire (id_soiree, nom) garantit au plus une ligne.
     */
    public function findBySoireeNom(string $idSoiree, string $nom): Tarif
    {
        $stmt = $this->pdo->prepare(
            'SELECT nom, prix
            FROM tarif
            WHERE id_soiree = :id AND nom = :nom
            '
        );
        $stmt->execute(['id' => $idSoiree, 'nom' => $nom]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new TarifNotFound("$nom (soirée $idSoiree)");
        }

        return new Tarif($row['nom'], (float) $row['prix']);
    }
}