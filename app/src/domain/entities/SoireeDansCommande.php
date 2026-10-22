<?php

namespace nrv\domain\entities;

use nrv\domain\exceptions\QuantiteInvalide;

// Il s'agit d'une soirée dans une commande. Elle contient l'identifiant de la soirée, le tarif choisi et la quantité de billets.
// C'est une ligne du panier : elle a son propre identifiant (colonne id_lignes), utilisé pour la supprimer.
class SoireeDansCommande
{
    private string $idLigne;
    private Soiree $soiree;
    private Tarif $tarif;
    private int $quantite;

    public function __construct(string $idLigne, Soiree $soiree, Tarif $tarif, int $quantite)
    {
        $this->idLigne = $idLigne;
        $this->soiree = $soiree;
        $this->tarif = $tarif;
        if ($quantite <= 0) {
            throw new QuantiteInvalide($quantite);
        } else {
            $this->quantite = $quantite;
        }
    }

    // sous-total de la ligne : prix du tarif × nombre de billets
    public function getMontant(): float
    {
        return $this->tarif->getPrix() * $this->quantite;
    }

    public function getSoiree(): Soiree
    {
        return $this->soiree;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function getTarif(): Tarif
    {
        return $this->tarif;
    }

    public function getIdLigne(): string
    {
        return $this->idLigne;
    }

    // ajoute des billets à la ligne (utilisé quand la même soirée et le même tarif sont ajoutés à nouveau)
    public function addQuantite(int $n): void
    {
        if ($n > 0) {
            $this->quantite += $n;
        }
    }

    // retire un billet ; à 0, Commande::supprimerSoiree() retire la ligne du panier
    public function decreaseQuantite(): void
    {
        if ($this->quantite!=0) {
            $this->quantite--;
        }
    }
}