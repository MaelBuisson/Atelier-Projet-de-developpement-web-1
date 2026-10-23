<?php

namespace nrv\adapters\persistence;

use nrv\application\ports\spi\JaugeRepository;
use nrv\domain\entities\CommandeStatus;
use nrv\application\dto\JaugeSoireeDTO;
use PDO;

class PgJaugeRepository implements JaugeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getJauges(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT s.id_soiree, s.nom, s.date, s.nb_place,
                    COUNT(v.token) AS places_vendues,
                    COALESCE(SUM(v.prix), 0) AS recette
             FROM soiree s
             LEFT JOIN (
                 SELECT b.token, b.id_soiree, t.prix
                 FROM billet b
                 JOIN commande c ON c.id_commande = b.id_commande AND c.statut = :statut
                 JOIN tarif t ON t.id_soiree = b.id_soiree AND t.nom = b.nom_tarif
             ) v ON v.id_soiree = s.id_soiree
             GROUP BY s.id_soiree, s.nom, s.date, s.nb_place
             ORDER BY s.date, s.horaire"
        );
        $stmt->execute(['statut' => CommandeStatus::PAYEE->name]);

        return array_map(
            fn(array $r) => new JaugeSoireeDTO(
                $r['id_soiree'],
                $r['nom'],
                (new \DateTimeImmutable($r['date']))->format('d/m/Y'),
                (int) $r['nb_place'],
                (int) $r['places_vendues'],
                (float) $r['recette'],
            ),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}