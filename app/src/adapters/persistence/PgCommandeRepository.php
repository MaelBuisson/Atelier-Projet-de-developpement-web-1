<?php

namespace nrv\adapters\persistence;

class PgCommandeRepository implements \nrv\application\ports\spi\CommandeRepository
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function getPanier(string $idUtilisateur): ?\nrv\domain\entities\Commande
    {
        $stmt = $this->pdo->prepare('SELECT * FROM commande WHERE id_utilisateur = :id_utilisateur AND statut = :statut');
        $stmt->execute(['id_utilisateur' => $idUtilisateur, 'statut' => 'PANIER']);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return null;
        }
        return new \nrv\domain\entities\Commande($row['id'], new \DateTimeImmutable($row['date']), new \nrv\domain\entities\Utilisateur($row['id_utilisateur'], '', '', '', ''));
    }
}