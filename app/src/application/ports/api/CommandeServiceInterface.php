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
}
