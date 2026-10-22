<?php

namespace nrv\domain\entities;

use DateTimeImmutable;

/**
 * Artiste (ou groupe) se produisant dans un ou plusieurs spectacles.
 * Le prénom est facultatif : un groupe n'a qu'un nom.
 */
class Commande
{
    private string $idCommande;
    private DateTimeImmutable $dateCréation;
    private float $montantTotal;
    private CommandeStatus $status;
    private Utilisateur $utilisateur;
    private array $soireesDansCommande = [];
    public function __construct(string $id, DateTimeImmutable $date, Utilisateur $utilisateur, float $montant = 0, CommandeStatus $status = CommandeStatus::PANIER)
    {
        $this->idCommande = $id;
        $this->dateCréation = $date;
        $this->montantTotal = $montant;
        $this->status = $status;
        $this->utilisateur = $utilisateur;
    }

    public function ajouterSoiree(SoireeDansCommande $soiree): void
    {
        $this->soireesDansCommande[] = $soiree;
        $this->montantTotal += $soiree->getMontant();
    }

    public function supprimerSoiree(string $idSoiree): void
    {
        foreach ($this->soireesDansCommande as $index => $soiree) {
            if ($soiree->getIdSoiree() === $idSoiree) {
                $this->montantTotal -= $soiree->getMontant();
                unset($this->soireesDansCommande[$index]);
                $this->soireesDansCommande = array_values($this->soireesDansCommande);
                return;
            }
        }
        throw new \InvalidArgumentException("La soirée avec l'ID spécifié n'existe pas dans la commande.");
    }

    public function valider(): void
    {
        if ($this->status !== CommandeStatus::PANIER) {
            throw new \LogicException("La commande ne peut être validée que si elle est dans le panier.");
        }
        $this->status = CommandeStatus::VALIDEE;
    }

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

    public function getDateCréation(): DateTimeImmutable
    {
        return $this->dateCréation;
    }

    public function getMontantTotal(): float
    {
        return $this->montantTotal;
    }

    public function getStatus(): CommandeStatus
    {
        return $this->status;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function getSoireesDansCommande(): array
    {
        return $this->soireesDansCommande;
    }
}