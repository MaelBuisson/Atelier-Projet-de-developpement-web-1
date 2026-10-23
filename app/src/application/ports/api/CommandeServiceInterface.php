<?php

namespace nrv\application\ports\api;

use nrv\application\dto\CommandeDTO;
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

    /**
     * Valide le panier de l'utilisateur : il devient une commande VALIDEE, qui ne peut plus être modifiée.
     *
     * @throws \nrv\domain\exceptions\PanierVide si l'utilisateur n'a pas de panier ou s'il est vide
     * @throws \nrv\domain\exceptions\CommandeNonValidable si la commande n'est plus un panier
     */
    public function valider(string $idUtilisateur): CommandeDTO;

    /**
     * Commande de l'utilisateur, quel que soit son statut.
     *
     * @throws \nrv\domain\exceptions\CommandeNotFound si elle n'existe pas ou appartient à un autre utilisateur
     */
    public function getCommande(string $idUtilisateur, string $idCommande): CommandeDTO;

    /**
     * Paie (paiement simulé) une commande validée de l'utilisateur et crée ses billets, un par place.
     *
     * @throws \nrv\domain\exceptions\CommandeNotFound si elle n'existe pas ou appartient à un autre utilisateur
     * @throws \nrv\domain\exceptions\CommandeNonPayable si elle n'est pas au statut VALIDEE
     * @throws \nrv\domain\exceptions\PlacesInsuffisantes si une soirée n'a plus assez de places
     */
    public function payer(string $idUtilisateur, string $idCommande): CommandeDTO;

    /**
     * Fonction pour avoir toutes les commandes d'un utilisateur
     * 
     * @param string $idUtilisateur
     * @return array une liste de commande
     */
    public function getAllCommandesByUser(string $idUtilisateur): array;
}
