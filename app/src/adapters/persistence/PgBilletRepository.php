<?php

namespace nrv\adapters\persistence;

use DateTimeImmutable;
use nrv\application\ports\spi\BilletRepository;
use nrv\domain\entities\Billet;
use nrv\domain\entities\Soiree;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Utilisateur;
use nrv\domain\exceptions\BilletNotFoundException;
use PDO;

class PgBilletRepository implements BilletRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getBillet(string $token): Billet
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.token, b.id_commande,
                    u.id_utilisateur, u.nom AS user_nom, u.prenom AS user_prenom,
                    s.id_soiree, s.nom AS soiree_nom, s.date AS soiree_date,
                    s.horaire AS soiree_horaire, s.lieu AS soiree_lieu,
                    s.thematique AS soiree_thematique, s.nb_place AS soiree_nb,
                    t.nom AS tarif_nom, t.prix AS tarif_prix
             FROM billet b
             JOIN commande c ON c.id_commande = b.id_commande
             JOIN utilisateur u ON u.id_utilisateur = c.id_utilisateur
             JOIN soiree s ON s.id_soiree = b.id_soiree
             JOIN tarif t ON t.id_soiree = b.id_soiree AND t.nom = b.nom_tarif
             WHERE b.token = :token"
        );
        $stmt->execute(['token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new BilletNotFoundException($token);
        }

        return $this->creerBillet($row);
    }

    /** @return Billet[] */
    public function getBilletsByCommandePayee(string $idCommande, string $idUtilisateur): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT b.token, b.id_commande,
                    u.id_utilisateur, u.nom AS user_nom, u.prenom AS user_prenom,
                    s.id_soiree, s.nom AS soiree_nom, s.date AS soiree_date,
                    s.horaire AS soiree_horaire, s.lieu AS soiree_lieu,
                    s.thematique AS soiree_thematique, s.nb_place AS soiree_nb,
                    t.nom AS tarif_nom, t.prix AS tarif_prix
             FROM billet b
             JOIN commande c ON c.id_commande = b.id_commande
             JOIN utilisateur u ON u.id_utilisateur = c.id_utilisateur
             JOIN soiree s ON s.id_soiree = b.id_soiree
             JOIN tarif t ON t.id_soiree = b.id_soiree AND t.nom = b.nom_tarif
             WHERE b.id_commande = :idCommande
               AND c.id_utilisateur = :idUtilisateur
               AND c.statut = 'PAYEE'
             ORDER BY s.date, b.token"
        );
        $stmt->execute(['idCommande' => $idCommande, 'idUtilisateur' => $idUtilisateur]);

        return array_map(
            fn(array $row) => $this->creerBillet($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    private function creerBillet(array $row): Billet
    {
        $soiree = new Soiree(
            $row['id_soiree'],
            $row['soiree_nom'],
            $row['soiree_thematique'],
            new DateTimeImmutable($row['soiree_date']),
            new DateTimeImmutable($row['soiree_horaire']),
            $row['soiree_lieu'],
            $row['soiree_nb'],
        );

        // seuls id, nom et prénom servent au billet : pas d'email ni de hash
        $titulaire = new Utilisateur($row['id_utilisateur'], '', $row['user_nom'], $row['user_prenom'], '');

        return new Billet(
            $row['token'],
            $row['id_commande'],
            $soiree,
            new Tarif($row['tarif_nom'], (float) $row['tarif_prix']),
            $titulaire
        );
    }
}