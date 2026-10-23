<?php

namespace nrv\application\dto;

use JsonSerializable;
use Override;

final readonly class JaugeSoireeDTO implements JsonSerializable
{
    public int $placesRestantes;
    public float $tauxRemplissage;

    public function __construct(
        public string $idSoiree,
        public string $nom,
        public string $date,
        public int $nbPlaces,
        public int $placesVendues,
        public float $recette,
    ) {
        $this->placesRestantes = $nbPlaces - $placesVendues;
        $this->tauxRemplissage = round($placesVendues / $nbPlaces * 100, 1);
    }

    #[Override]
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}