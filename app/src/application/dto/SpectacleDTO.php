<?php

namespace nrv\application\dto;

use nrv\domain\entities\Spectacle;

final class SpectacleDTO
{
    private function __construct(
        public readonly string $idSpectacle,
        public readonly string $titre,
        public readonly string $date,
        public readonly string $horaire,
        public readonly string $image,
    ) {
    }

    public static function fromEntity(Spectacle $s): self
    {
        return new self(
            $s->getId(),
            $s->getTitre(),
            $s->getSoiree()->getDate()->format('Y-m-d'),
            $s->getHoraire()->format('Y-m-d'),
            $s->getImage() ?? null
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->idSpectacle,
            'titre' => $this->titre,
            'date' => $this->date,
            'horaire' => $this->horaire,
            'image' => $this->image,
        ];
    }
}