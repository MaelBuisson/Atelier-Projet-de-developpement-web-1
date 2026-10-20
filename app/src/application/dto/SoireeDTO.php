<?php

namespace nrv\application\dto;

use nrv\domain\entities\Soiree;

/**
 * DTO d'une soirée, sans ses spectacles ni ses tarifs (utilisé pour les listes).
 * Les dates sont déjà formatées en chaînes pour l'API.
 */
final readonly class SoireeDTO implements \JsonSerializable
{
    private function __construct(
        public string $idSoiree,
        public string $nom,
        public string $thematique,
        public string $date,
        public string $horaire,
        public string $lieu,
        public int $nbPlace
    ) {
    }

    // construit le DTO à partir de l'entité : le domaine ne sort pas de la couche application
    public static function fromEntity(Soiree $s): self
    {
        return new self(
            $s->getId(),
            $s->getNom(),
            $s->getThematique(),
            $s->getDate()->format('Y-m-d'),
            $s->getHoraire()->format('H:i'),
            $s->getLieu(),
            $s->getNbPlace()
        );
    }

    // appelée automatiquement par json_encode grâce à JsonSerializable
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->idSoiree,
            'nom' => $this->nom,
            'thematique' => $this->thematique,
            'date' => $this->date,
            'horaire' => $this->horaire,
            'lieu' => $this->lieu,
            'nbPlace' => $this->nbPlace //a' changer: nbPlace restant et pas total 
        ];
    }
}