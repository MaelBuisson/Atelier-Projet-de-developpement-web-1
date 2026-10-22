<?php

namespace nrv\adapters\persistence;

use DateTimeImmutable;
use PDO;
use nrv\application\ports\spi\CommandeRepository;
use nrv\domain\entities\Commande;
use nrv\domain\entities\CommandeStatus;
use nrv\domain\entities\Soiree;
use nrv\domain\entities\SoireeDansCommande;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Utilisateur;

class PgCommandeRepository implements CommandeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getPanier(string $idUtilisateur): ?Commande
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id_commande, c.date_creation,
                    sdc.id_lignes, sdc.quantite,
                    so.id_soiree, so.nom AS soiree_nom, so.date AS soiree_date, so.thematique AS soiree_thematique,
                    so.nb_places AS soiree_nb_places, so.horaires AS soiree_horaires, so.lieu AS soiree_lieu,
                    t.nom AS tarif_nom, t.prix AS tarif_prix
             FROM commande c
             LEFT JOIN soiree_dans_commande sdc ON sdc.id_commande = c.id_commande
             LEFT JOIN soiree so ON so.id_soiree = sdc.id_soiree
             LEFT JOIN tarif t ON t.id_soiree = sdc.id_soiree AND t.nom = sdc.nom_tarif
             WHERE c.id_utilisateur = :id_utilisateur AND c.statut = :statut
             ORDER BY sdc.id_lignes"
        );
        $stmt->execute([
            'id_utilisateur' => $idUtilisateur,
            'statut' => CommandeStatus::PANIER->name, // 'PANIER'
        ]);
        $rows = $stmt->fetchAll(this->pdo::FETCH_ASSOC);

        if ($rows === []) {
            return null;
        }

        $commande = new Commande(
            $rows[0]['id_commande'],
            new DateTimeImmutable($rows[0]['date_creation']),
            new Utilisateur($idUtilisateur, '', '', '', '')
        );

        foreach ($rows as $row) {
            if ($row['id_lignes'] === null) {
                continue;
            }

            $commande->ajouterSoiree(new SoireeDansCommande(
                $row['id_lignes'],
                new Soiree(
                    $row['id_soiree'],
                    $row['soiree_nom'],
                    $row['soiree_thematique'],
                    new DateTimeImmutable($row['soiree_date']),
                    new DateTimeImmutable($row['soiree_horaires']),
                    $row['soiree_lieu'],
                    (int) $row['soiree_nb_places']
                ),
                new Tarif($row['tarif_nom'], (float) $row['tarif_prix']),
                (int) $row['quantite']
            ));
        }

        return $commande;
    }
}