<?php

namespace nrv\application\dto;

use nrv\domain\entities\Soiree;
use nrv\domain\entities\Tarif;
use nrv\domain\entities\Spectacle;
use Override;

/**
 * DTO du détail d'une soirée : ses informations, les tarifs du festival et la liste de ses spectacles.
 * Il regroupe plusieurs entités, car aucune entité seule n'a cette forme.
 */
final readonly class SoireeDetailDTO implements \JsonSerializable
{
    private function __construct(
        public string $idSoiree,
        public string $nom,
        public string $thematique,
        public string $date,
        public string $horaire,
        public string $lieu,
        public array $tarifs,       // [['nom' => 'normal', 'prix' => 20.0], …]
        public array $spectacles   // SpectacleDetailDTO[]
    ) {
    }

    /**
     * Construit le DTO à partir des entités chargées par les trois repositories.
     * Les tarifs deviennent des tableaux simples et chaque spectacle un SpectacleDetailDTO.
     *
     * @param Spectacle[] $spectacles
     * @param Tarif[]     $tarifs
     */
    public static function fromEntities(Soiree $s, array $spectacles, array $tarifs): self
    {
        return new self(
            $s->getId(),
            $s->getNom(),
            $s->getThematique(),
            $s->getDate()->format('Y-m-d'),
            $s->getHoraire()->format('H:i'),
            $s->getLieu(),
            array_map(
                fn(Tarif $t) => ['nom' => $t->getNom(), 'prix' => $t->getPrix()],
                $tarifs
            ),
            array_map(SpectacleDetailDTO::fromEntity(...), $spectacles)
        );
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->idSoiree,
            'nom' => $this->nom,     
            'thematique' => $this->thematique,
            'date' => $this->date,    
            'horaire' => $this->horaire, 
            'lieu' => $this->lieu,    
            'tarifs' => $this->tarifs,   
            'spectacles' => $this->spectacles
        ];
    }
}