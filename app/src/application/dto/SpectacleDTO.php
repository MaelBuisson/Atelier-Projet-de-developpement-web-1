<?php

namespace nrv\application\dto;

use nrv\domain\entities\Spectacle;

/**
 * DTO d'un spectacle dans la liste du programme : titre, date, horaire et première image.
 */
final class SpectacleDTO implements \JsonSerializable
{
    private function __construct(
        public readonly string $idSpectacle,
        public readonly string $titre,
        public readonly string $date,
        public readonly string $horaire,
        public readonly ?string $image,
        public readonly string $idSoiree
    ) {
    }

    /**
     * Construit le DTO à partir de l'entité.
     * Le spectacle doit être rattaché à sa soirée (c'est elle qui porte la date) ;
     * image vaut null si le spectacle n'en a aucune.
     */
    public static function fromEntity(Spectacle $s): self
    {
        return new self(
            $s->getId(),
            $s->getTitre(),
            $s->getSoiree()->getDate()->format('Y-m-d'),
            $s->getHoraire()->format('H:i'),
            $s->getImage()?->getUrl() ?? null,
            $s->getSoiree()->getId()
        );
    }

    // appelée automatiquement par json_encode grâce à JsonSerializable
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->idSpectacle,
            'titre' => $this->titre,
            'date' => $this->date,
            'horaire' => $this->horaire,
            'image' => $this->image,
            'idSoiree' => $this->idSoiree
        ];
    }
}