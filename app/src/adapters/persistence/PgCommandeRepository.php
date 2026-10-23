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
use nrv\domain\exceptions\CommandeNonPayable;
use nrv\domain\exceptions\PlacesInsuffisantes;

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
        $where = 'WHERE c.id_utilisateur = :id_utilisateur AND c.statut = :statut';
        $params = [
            'id_utilisateur' => $idUtilisateur,
            'statut' => CommandeStatus::PANIER->name,
        ];
        return $this->toCommande($where, $params);
    }

    /**
     * Enregistre la commande et ses lignes : insertion si elle est nouvelle, mise à jour sinon.
     * Tout se fait dans une transaction : en cas d'erreur, la base reste dans son état précédent.
     */
    public function save(Commande $commande): void
    {
        $this->pdo->beginTransaction();

        try {
            // upsert : date et utilisateur ne changent jamais, seuls le statut et le montant sont mis à jour
            $stmt = $this->pdo->prepare(
                "INSERT INTO commande (id_commande, date_creation, montant_total, statut, id_utilisateur)
                VALUES (:id_commande, :date_creation, :montant_total, :statut, :id_utilisateur)
                ON CONFLICT (id_commande)
                DO UPDATE SET statut = EXCLUDED.statut, montant_total = EXCLUDED.montant_total
                "
            );
            $stmt->execute([
                'id_commande' => $commande->getId(),
                'date_creation' => $commande->getDateCreation()->format('Y-m-d H:i:s'),
                'montant_total' => $commande->getMontantTotal(),
                'statut' => $commande->getStatus()->name,
                'id_utilisateur' => $commande->getUtilisateur()->getId()
            ]);

            $sdcs = $commande->getSoireesDansCommande();

            // suppression des lignes retirées du panier, AVANT les upserts : si une soirée/tarif est
            // supprimée puis rajoutée avec un nouvel id, l'ancienne ligne bloquerait la contrainte UNIQUE.
            // Panier vide : pas de NOT IN (NOT IN () est une erreur SQL), toutes les lignes sont supprimées.
            $notIn = '';
            $params = [$commande->getId()];

            if ($sdcs) {
                $ids = array_map(
                    fn($sdc) => $sdc->getIdLigne(),
                    $sdcs
                );

                // un ? par identifiant : une liste ne peut pas être liée à un seul paramètre
                $placeholders = implode(', ', array_fill(0, count($ids), '?'));

                $notIn = "AND id_ligne NOT IN ($placeholders)";

                $params = array_merge(
                    $params,
                    $ids
                );
            }

            $stmt = $this->pdo->prepare(
                "DELETE FROM soiree_dans_commande
                WHERE id_commande = ? $notIn
                "
            );

            $stmt->execute($params);

            // upsert des lignes : requête préparée une seule fois, exécutée pour chaque ligne
            if ($sdcs) {
                $stmt = $this->pdo->prepare(
                    'INSERT INTO soiree_dans_commande (id_ligne, id_commande, id_soiree, nom_tarif, quantite)
                    VALUES (:id_ligne, :id_commande, :id_soiree, :nom_tarif, :quantite)
                    ON CONFLICT (id_ligne)
                    DO UPDATE SET quantite = EXCLUDED.quantite
                    '
                );

                foreach ($sdcs as $sdc) {
                    $stmt->execute([
                        'id_ligne' => $sdc->getIdLigne(),
                        'id_commande' => $commande->getId(),
                        'id_soiree' => $sdc->getSoiree()->getId(),
                        'nom_tarif' => $sdc->getTarif()->getNom(),
                        'quantite' => $sdc->getQuantite()
                    ]);
                }
            }
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $this->pdo->commit();
    }

    /**
     * Paiement en une transaction : verrou sur la commande et sur ses soirées (SELECT … FOR UPDATE),
     * vérification des places restantes, puis statut PAYEE et insertion des billets.
     * Le verrou fait attendre un second paiement sur les mêmes soirées jusqu'au COMMIT du premier :
     * il compte alors les billets déjà vendus et ne peut pas dépasser la capacité.
     */
    public function enregistrerPaiement(Commande $commande, array $billets): void
    {
        $this->pdo->beginTransaction();

        try {
            // la commande doit toujours être validée en base : protège contre un double paiement simultané
            $stmt = $this->pdo->prepare('SELECT statut FROM commande WHERE id_commande = :id FOR UPDATE');
            $stmt->execute(['id' => $commande->getId()]);
            $statut = $stmt->fetchColumn();
            if ($statut !== CommandeStatus::VALIDEE->name) {
                throw new CommandeNonPayable($statut === false ? CommandeStatus::PANIER : CommandeStatus::fromName($statut));
            }

            $demandees = $commande->getPlacesParSoiree();
            // ordre fixe des verrous (par id) : deux paiements ne peuvent pas s'attendre l'un l'autre (interblocage)
            ksort($demandees);
            $ids = array_keys($demandees);
            $placeholders = implode(', ', array_fill(0, count($ids), '?'));

            $stmt = $this->pdo->prepare(
                "SELECT id_soiree, nom, nb_place FROM soiree WHERE id_soiree IN ($placeholders) ORDER BY id_soiree FOR UPDATE"
            );
            $stmt->execute($ids);
            $soirees = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $soirees[$row['id_soiree']] = $row;
            }

            // places déjà vendues : un billet émis = une place occupée
            $stmt = $this->pdo->prepare(
                "SELECT id_soiree, COUNT(*) AS vendues FROM billet WHERE id_soiree IN ($placeholders) GROUP BY id_soiree"
            );
            $stmt->execute($ids);
            $vendues = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

            foreach ($demandees as $idSoiree => $nombre) {
                $restantes = (int) $soirees[$idSoiree]['nb_place'] - (int) ($vendues[$idSoiree] ?? 0);
                if ($nombre > $restantes) {
                    throw new PlacesInsuffisantes($soirees[$idSoiree]['nom'], max(0, $restantes), $nombre);
                }
            }

            $stmt = $this->pdo->prepare('UPDATE commande SET statut = :statut WHERE id_commande = :id');
            $stmt->execute(['statut' => $commande->getStatus()->name, 'id' => $commande->getId()]);

            $stmt = $this->pdo->prepare(
                'INSERT INTO billet (token, id_soiree, id_commande, nom_tarif)
                VALUES (:token, :id_soiree, :id_commande, :nom_tarif)'
            );
            foreach ($billets as $billet) {
                $stmt->execute([
                    'token' => $billet->getToken(),
                    'id_soiree' => $billet->getSoiree()->getId(),
                    'id_commande' => $billet->getIdCommande(),
                    'nom_tarif' => $billet->getTarif()->getNom(),
                ]);
            }
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        $this->pdo->commit();
    }

    /**
     * Retourne la commande (quel que soit son statut) avec ses lignes, ou null si elle n'existe pas.
     */
    public function findById(string $idCommande): ?Commande
    {
        $where = 'WHERE c.id_commande = :id_commande';
        $params = [
            'id_commande' => $idCommande
        ];
        return $this->toCommande($where, $params);
    }

    public function findAllByUtilisateur(string $idUtilisateur): array
    {
        return $this->toCommandes(
            'WHERE c.id_utilisateur = :id_utilisateur',
            ['id_utilisateur' => $idUtilisateur]
        );
    }

    // On garde la première commande trouvée
    private function toCommande(string $where, array $params): ?Commande
    {
        return $this->toCommandes($where, $params)[0] ?? null;
    }

    /**
     * Même requête qu'avant, mais renvoie toutes les commandes trouvées, avec leurs lignes.
     * @return Commande[]
     */
    private function toCommandes(string $where, array $params): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.id_commande, c.date_creation, c.statut,
                u.id_utilisateur, u.email, u.nom AS utilisateur_nom, u.prenom AS utilisateur_prenom,
                u.password AS utilisateur_password, u.role AS utilisateur_role,
                sdc.id_ligne, sdc.quantite,
                so.id_soiree, so.nom AS soiree_nom, so.thematique AS soiree_thematique,
                so.date AS soiree_date, so.horaire AS soiree_horaire,
                so.lieu AS soiree_lieu, so.nb_place AS soiree_nb_place,
                t.nom AS tarif_nom, t.prix AS tarif_prix
         FROM commande c
         JOIN utilisateur u ON u.id_utilisateur = c.id_utilisateur
         LEFT JOIN soiree_dans_commande sdc ON sdc.id_commande = c.id_commande
         LEFT JOIN soiree so ON so.id_soiree = sdc.id_soiree
         LEFT JOIN tarif t ON t.id_soiree = sdc.id_soiree AND t.nom = sdc.nom_tarif
         $where
         ORDER BY c.date_creation DESC, c.id_commande, so.date, so.horaire, t.nom"
        );
        $stmt->execute($params);

        // regroupement des rangées par commande 
        $parCommande = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $parCommande[$row['id_commande']][] = $row;
        }

        $commandes = [];
        foreach ($parCommande as $rows) {
            $soirees = [];
            $lignes = [];

            foreach ($rows as $row) {
                if ($row['id_ligne'] === null) {
                    continue; // commande sans ligne
                }

                $soirees[$row['id_soiree']] ??= new Soiree(
                    $row['id_soiree'],
                    $row['soiree_nom'],
                    $row['soiree_thematique'],
                    new DateTimeImmutable($row['soiree_date']),
                    DateTimeImmutable::createFromFormat('!H:i:s', $row['soiree_horaire']),
                    $row['soiree_lieu'],
                    (int) $row['soiree_nb_place']
                );

                $lignes[] = new SoireeDansCommande(
                    $row['id_ligne'],
                    $soirees[$row['id_soiree']],
                    new Tarif($row['tarif_nom'], (float) $row['tarif_prix']),
                    (int) $row['quantite']
                );
            }

            $premiere = $rows[0];

            $commandes[] = new Commande(
                $premiere['id_commande'],
                new DateTimeImmutable($premiere['date_creation']),
                new Utilisateur(
                    $premiere['id_utilisateur'],
                    $premiere['email'],
                    $premiere['utilisateur_nom'],
                    $premiere['utilisateur_prenom'],
                    $premiere['utilisateur_password'],
                    (int) $premiere['utilisateur_role']
                ),
                CommandeStatus::fromName($premiere['statut']),
                $lignes
            );
        }

        return $commandes;
    }
}
