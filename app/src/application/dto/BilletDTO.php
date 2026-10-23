<?php

namespace nrv\application\dto;

use JsonSerializable;
use nrv\domain\entities\Billet;
use Override;

/**
 * DTO d'un billet tel qu'il apparaît :
 * référence, nom, prénom, date, heure et tarif.
 */
final readonly class BilletDTO implements JsonSerializable
{
    private function __construct(
        public string $nom,
        public string $prenom,
        public string $reference,
        public string $date,
        public string $heure,
        public string $tarif,
    ) {
    }

    /**
     * Construit le DTO à partir de l'entité.
     * Le token servira de référence pour le billet, et la date et l'heure sont formatées pour l'affichage.
     */
    public static function fromEntity(Billet $billet): self
    {
        return new self(
            $billet->getTitulaire()->getNom(),
            $billet->getTitulaire()->getPrenom(),
            $billet->getToken(),
            $billet->getSoiree()->getDate()->format('d/m/Y'),
            $billet->getSoiree()->getHoraire()->format('H:i'),
            $billet->getTarif()->getNom(),
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'reference' => $this->reference,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'date' => $this->date,
            'heure' => $this->heure,
            'tarif' => $this->tarif
        ];
    }
}