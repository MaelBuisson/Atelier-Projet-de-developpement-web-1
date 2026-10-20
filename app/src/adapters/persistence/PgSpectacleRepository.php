<?php

namespace nrv\adapters\persistence;

use DateTimeImmutable;
use nrv\application\ports\spi\SpectacleRepository;
use nrv\domain\entities\Soiree;
use nrv\domain\entities\Spectacle;
use PDO;

/**
 * Adaptateur de sortie : accès aux spectacles stockés dans PostgreSQL.
 * Implémente le port SPI SpectacleRepository défini dans la couche application.
 */
class PgSpectacleRepository implements SpectacleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retourne tous les spectacles, triés par date de soirée puis par horaire.
     * Chaque spectacle est rattaché à sa soirée (nécessaire pour afficher la date dans la liste).
     *
     * @return Spectacle[]
     */
    public function findAll(): array
    {
        // les colonnes de soiree sont renommées (alias) car spectacle et soiree ont toutes
        // deux une colonne horaire : sans alias, l'une écraserait l'autre dans le tableau associatif
        $stmt = $this->pdo->prepare(
            'SELECT spectacle.id_spectacle, spectacle.titre, spectacle.description, spectacle.horaire,
                    spectacle.video, spectacle.style_musique,
                    soiree.id_soiree, soiree.nom AS soiree_nom, soiree.thematique AS soiree_thematique,
                    soiree.date AS soiree_date, soiree.horaire AS soiree_horaire,
                    soiree.lieu AS soiree_lieu, soiree.nb_place AS soiree_nb_place
            FROM spectacle
            JOIN soiree ON spectacle.id_soiree = soiree.id_soiree
            ORDER BY soiree.date, spectacle.horaire'
        );
        $stmt->execute();

        $spectacles = [];

        // soirées déjà construites, indexées par id : les spectacles d'une même soirée
        // partagent ainsi le même objet Soiree au lieu d'en créer une copie par spectacle
        $soirees = [];

        // fetchAll : récupère toutes les lignes (fetch n'en retourne qu'une seule)
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $soirees[$row['id_soiree']] ??= new Soiree(
                $row['id_soiree'],
                $row['soiree_nom'],
                $row['soiree_thematique'],
                new DateTimeImmutable($row['soiree_date']),
                DateTimeImmutable::createFromFormat('!H:i:s', $row['soiree_horaire']),
                $row['soiree_lieu'],
                (int) $row['soiree_nb_place']
            );

            $spectacle = $this->toSpectacle($row);
            $spectacle->assignSoiree($soirees[$row['id_soiree']]);
            $spectacles[] = $spectacle;
        }

        return $spectacles;
    }

    /**
     * Retourne les spectacles d'une soirée, triés par horaire.
     * Une soirée sans spectacle donne un tableau vide : ce n'est pas une erreur.
     *
     * @return Spectacle[]
     */
    public function getBySoiree(string $id): array
    {
        // requête préparée : l'identifiant est passé en paramètre, ce qui protège des injections SQL
        $stmt = $this->pdo->prepare(
            'SELECT id_spectacle, titre, description, horaire, video, style_musique
            FROM spectacle
            WHERE id_soiree = :id
            ORDER BY horaire'
        );
        $stmt->execute(['id' => $id]);

        $spectacles = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $spectacles[] = $this->toSpectacle($row);
        }

        return $spectacles;
    }

    /**
     * Construit une entité Spectacle à partir d'une ligne de la table spectacle.
     * La colonne horaire (TIME) est convertie avec '!H:i:s' : le '!' fixe la date au 1970-01-01
     * au lieu de la date du jour, pour que l'objet ne change pas d'un jour à l'autre.
     */
    private function toSpectacle(array $row): Spectacle
    {
        return new Spectacle(
            $row['id_spectacle'],
            $row['titre'],
            $row['description'],
            DateTimeImmutable::createFromFormat('!H:i:s', $row['horaire']),
            $row['video'],
            $row['style_musique']
        );
    }
}
