<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\TarifRepository;
use nrv\domain\entities\Tarif;

/**
 * Faux repository des tarifs : les tarifs sont rangés en mémoire par soirée.
 */
class FakeTarifRepository implements TarifRepository
{
    /** @param array<string, Tarif[]> $parSoiree id de soirée => ses tarifs */
    public function __construct(private array $parSoiree = [])
    {
    }

    public function findBySoiree(string $idSoiree): array
    {
        return $this->parSoiree[$idSoiree] ?? [];
    }
}
