<?php

namespace nrv\application\ports\api;

use nrv\application\dto\PanierDTO;

/**
 * Port d'entrée (API) : ce que l'application propose sur le panier et les commandes.
 * Les actions Slim n'utilisent que cette interface, jamais l'implémentation.
 */
interface CommandeServiceInterface
{
    /**
     * Panier de l'utilisateur (vide s'il n'en a pas encore).
     *
     * @throws \nrv\domain\exceptions\UserNotFound si l'utilisateur n'existe plus
     */
    public function getPanier(string $idUtilisateur): PanierDTO;

    /**
     * Ajoute des billets au panier (créé s'il n'existe pas), au prix du tarif enregistré en base.
     * Une ligne existante pour la même soirée et le même tarif voit sa quantité augmenter.
     *
     * @throws \nrv\domain\exceptions\SoireeNotFound si la soirée n'existe pas
     * @throws \nrv\domain\exceptions\TarifNotFound si la soirée n'a pas ce tarif
     * @throws \nrv\domain\exceptions\QuantiteInvalide si la quantité est inférieure à 1
     * @throws \nrv\domain\exceptions\UserNotFound si l'utilisateur n'existe plus
     */
    public function ajouterAuPanier(string $idUtilisateur, string $idSoiree, string $nomTarif, int $quantite): PanierDTO;

    /**
     * Retire un billet de la ligne du panier ; la ligne disparaît quand elle n'a plus de billet.
     *
     * @throws \nrv\domain\exceptions\SoireeDansCommandeNotFound si la ligne n'est pas dans le panier de l'utilisateur
     */
    public function supprimerDuPanier(string $idUtilisateur, string $idLigne): PanierDTO;
}
