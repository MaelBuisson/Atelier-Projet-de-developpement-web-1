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

/**
 * Adaptateur de sortie : accès aux commandes (et au panier) stockés dans PostgreSQL.
 * Le statut est enregistré sous forme de texte : le nom du cas de CommandeStatus ('PANIER', 'VALIDEE', 'PAYEE').
 */
class PgCommandeRepository implements CommandeRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retourne le panier (commande au statut PANIER) de l'utilisateur avec ses lignes,
     * ou null s'il n'en a pas encore.
     */
    public function getPanier(string $idUtilisateur): ?Commande
    {
        // une seule requête : la commande, son utilisateur, et pour chaque ligne la soirée et le tarif.
        // LEFT JOIN sur les lignes : un panier sans ligne donne une seule rangée, colonnes de ligne à NULL.
        // Les alias évitent les collisions de noms (nom, date, horaire existent dans plusieurs tables).
        $stmt = $this->pdo->prepare(
            'SELECT c.id_commande, c.date_creation,
                    u.id_utilisateur, u.email, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom,
                    u.password AS utilisateur_password, u.role AS utilisateur_role,
                    sdc.id_lignes, sdc.quantite,
                    so.id_soiree, so.nom AS soiree_nom, so.thematique AS soiree_thematique,
                    so.date AS soiree_date, so.horaire AS soiree_horaire,
                    so.lieu AS soiree_lieu, so.nb_place AS soiree_nb_place,
                    t.nom AS tarif_nom, t.prix AS tarif_prix
             FROM commande c
             JOIN utilisateur u ON u.id_utilisateur = c.id_utilisateur
             LEFT JOIN soiree_dans_commande sdc ON sdc.id_commande = c.id_commande
             LEFT JOIN soiree so ON so.id_soiree = sdc.id_soiree
             LEFT JOIN tarif t ON t.id_soiree = sdc.id_soiree AND t.nom = sdc.nom_tarif
             WHERE c.id_utilisateur = :id_utilisateur AND c.statut = :statut
             ORDER BY so.date, so.horaire, t.nom'
        );
        $stmt->execute([
            'id_utilisateur' => $idUtilisateur,
            'statut' => CommandeStatus::PANIER->name,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return null;
        }

        $premiere = $rows[0];
        $commande = new Commande(
            $premiere['id_commande'],
            new DateTimeImmutable($premiere['date_creation']),
            new Utilisateur(
                $premiere['id_utilisateur'],
                $premiere['email'],
                $premiere['utilisateur_nom'],
                $premiere['utilisateur_prenom'],
                $premiere['utilisateur_password'],
                (int) $premiere['utilisateur_role']
            )
        );

        // soirées déjà construites, indexées par id : les lignes d'une même soirée partagent le même objet
        $soirees = [];

        foreach ($rows as $row) {
            // panier sans ligne : la rangée ne contient que la commande
            if ($row['id_lignes'] === null) {
                continue;
            }

            $soirees[$row['id_soiree']] ??= new Soiree(
                $row['id_soiree'],
                $row['soiree_nom'],
                $row['soiree_thematique'],
                new DateTimeImmutable($row['soiree_date']),
                // colonne TIME : '!' fixe la date au 1970-01-01 au lieu de la date du jour
                DateTimeImmutable::createFromFormat('!H:i:s', $row['soiree_horaire']),
                $row['soiree_lieu'],
                (int) $row['soiree_nb_place']
            );

            $commande->ajouterSoiree(new SoireeDansCommande(
                $row['id_lignes'],
                $soirees[$row['id_soiree']],
                new Tarif($row['tarif_nom'], (float) $row['tarif_prix']),
                (int) $row['quantite']
            ));
        }

        return $commande;
    }
}
