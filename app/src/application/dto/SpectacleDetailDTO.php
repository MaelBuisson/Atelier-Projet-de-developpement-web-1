<?php

namespace nrv\application\dto;

use JsonSerializable;
use nrv\domain\entities\Spectacle;
use nrv\domain\entities\Artiste;
use Override;

/**
 * DTO d'un spectacle tel qu'il apparaît dans le détail d'une soirée :
 * titre, artistes, description, horaire, vidéo et style de musique.
 */
final readonly class SpectacleDetailDTO implements JsonSerializable
{
    private function __construct(
        public string $idSpectacle,
        public string $titre,
        public array $artistes,
        public ?string $description,
        public string $horaire,
        public ?string $video,
        public string $styleMusique
    ) {
    }

    /**
     * Construit le DTO à partir de l'entité.
     * Les artistes sont transformés en chaînes « prénom nom » (trim retire l'espace si le prénom est absent).
     */
    public static function fromEntity(Spectacle $s): self
    {
        return new self(
            $s->getId(),
            $s->getTitre(),
            array_map(
                fn(Artiste $a) => trim($a->getPrenom() . ' ' . $a->getNom()),
                $s->getArtistes()
            ),
            $s->getDescription(),
            $s->getHoraire()->format('H:i'),
            $s->getVideo(),
            $s->getStyleMusique()
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->idSpectacle,
            'titre' => $this->titre,
            'artistes' => $this->artistes,
            'description' => $this->description,
            'horaire' => $this->horaire,
            'video' => $this->video,
            'styleMusique' => $this->styleMusique
        ];
    }
}