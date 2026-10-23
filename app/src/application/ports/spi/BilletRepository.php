<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Billet;
interface BilletRepository
{
    // Fonction pour avoir un billet avec son token
    public function getBillet(string $token): Billet;

    // Fonction pour avoir tous les billets d'une commande d'un utilisateur
    public function getBilletsByCommandePayee(string $idCommande, string $idUtilisateur): array;

    // Fonction pour créer un billet
    private function creerBillet(array $row): Billet;
}