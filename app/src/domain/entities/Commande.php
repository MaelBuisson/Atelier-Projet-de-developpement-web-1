<?php

namespace nrv\domain\entities;

use nrv\domain\exceptions\SoireeDansCommandeNotFound;
use nrv\domain\exceptions\PanierVide;
use DateTimeImmutable;

/**
 * Il s'agit d'une Commande, qui peut contenir plusieurs soirées.
 * Chaque commande est associée à un utilisateur.
 * Elle passe par différents statuts : PANIER, VALIDEE, PAYEE.
 * Tant qu'elle est au statut PANIER, c'est le panier de l'utilisateur.
 */
class Commande
{
    private string $idCommande;
    private DateTimeImmutable $dateCreation;
    private CommandeStatus $status;
    private Utilisateur $utilisateur;

    /** @var array<string, SoireeDansCommande> lignes du panier, indexées par leur identifiant */
    private array $soireesDansCommande = [];

    public function __construct(string $id, DateTimeImmutable $date, Utilisateur $utilisateur, CommandeStatus $status = CommandeStatus::PANIER)
    {
        $this->idCommande = $id;
        $this->dateCreation = $date;
        $this->status = $status;
        $this->utilisateur = $utilisateur;
    }

    /**
     * Ajoute des billets au panier.
     * Si le panier contient déjà la même soirée au même tarif, la quantité est ajoutée
     * à cette ligne (qui garde son identifiant) : une seule ligne par couple soirée + tarif,
     * comme l'impose la contrainte UNIQUE de la table soiree_dans_commande.
     *
     * @throws \LogicException si la commande n'est plus un panier
     */
    public function ajouterSoiree(SoireeDansCommande $soiree): void
    {
        if ($this->status !== CommandeStatus::PANIER) {
            throw new \LogicException("La commande ne peut être modifiée que si elle est dans le panier.");
        }

        if (!isset($this->soireesDansCommande[$soiree->getIdLignes()])) {
            // recherche d'une ligne existante pour la même soirée et le même tarif
            foreach ($this->soireesDansCommande as $sdc) {
                if($sdc->getSoiree()->getId().$sdc->getTarif()->getNom() === $soiree->getSoiree()->getId().$soiree->getTarif()->getNom()){
                    $this->soireesDansCommande[$sdc->getIdLignes()]->addQuantite($soiree->getQuantite());

                    return;
                }
            }

            // aucune ligne équivalente : nouvelle ligne
            $this->soireesDansCommande[$soiree->getIdLignes()] = $soiree;
        }
        else {
            // même identifiant de ligne : on ajoute la quantité
            $this->soireesDansCommande[$soiree->getIdLignes()]->addQuantite($soiree->getQuantite());
            
        }
    }

    /**
     * Retire un billet de la ligne ; la ligne disparaît du panier quand elle n'a plus de billet.
     *
     * @throws \LogicException si la commande n'est plus un panier
     * @throws SoireeDansCommandeNotFound si le panier ne contient pas cette ligne
     */
    public function supprimerSoiree(string $idLignes): void
    {
        if ($this->status !== CommandeStatus::PANIER) {
            throw new \LogicException("La commande ne peut être modifiée que si elle est dans le panier.");
        }

        if(isset($this->soireesDansCommande[$idLignes])) {
            $this->soireesDansCommande[$idLignes]->decreaseQuantite();
            if ($this->soireesDansCommande[$idLignes]->getQuantite()==0) {
                unset($this->soireesDansCommande[$idLignes]);
            }
            return;
        }
        
        throw new SoireeDansCommandeNotFound($idLignes);
    }

    /**
     * Transforme le panier en commande validée : il ne peut plus être modifié.
     * Les vérifications sont faites avant le changement de statut : en cas de refus,
     * la commande reste un panier.
     *
     * @throws \LogicException si la commande n'est plus un panier
     * @throws PanierVide si le panier ne contient aucune ligne
     */
    public function valider(): void
    {
        if ($this->status !== CommandeStatus::PANIER) {
            throw new \LogicException("La commande ne peut être validée que si elle est dans le panier.");
        }

        if ($this->soireesDansCommande === []) {
            throw new PanierVide();
        }
        $this->status = CommandeStatus::VALIDEE;
    }

    /**
     * Marque la commande comme payée (paiement simulé).
     *
     * @throws \LogicException si la commande n'a pas été validée
     */
    public function payer(): void
    {
        if ($this->status !== CommandeStatus::VALIDEE) {
            throw new \LogicException("La commande ne peut être payée que si elle est validée.");
        }
        $this->status = CommandeStatus::PAYEE;
    }

    public function getId(): string
    {
        return $this->idCommande;
    }

    public function getDateCreation(): DateTimeImmutable
    {
        return $this->dateCreation;
    }

    // total calculé à partir des lignes (et non stocké) : il ne peut pas devenir faux
    public function getMontantTotal(): float
    {
        $total = 0.0;
        foreach ($this->soireesDansCommande as $soiree) {
            $total += $soiree->getMontant();
        }
        return $total;
    }

    public function getStatus(): CommandeStatus
    {
        return $this->status;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    /**
     * @return SoireeDansCommande[] les lignes sous forme de liste (0, 1, 2…),
     *                              pour que json_encode produise un tableau et non un objet
     */
    public function getSoireesDansCommande(): array
    {
        return array_values($this->soireesDansCommande);
    }
}