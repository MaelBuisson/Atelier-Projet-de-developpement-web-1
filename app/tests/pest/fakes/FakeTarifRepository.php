<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\TarifRepository;
use nrv\domain\entities\Tarif;
use nrv\domain\exceptions\TarifNotFound;
use Override;

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

    // même contrat que PgTarifRepository : le tarif de cette soirée portant ce nom, sinon TarifNotFound
    public function findBySoireeNom(string $idSoiree, string $nom): Tarif
    {
        $tarifs = $this->parSoiree[$idSoiree] ?? [];

        foreach ($tarifs as $tarif) {
            if ($tarif->getNom() === $nom) {
                return $tarif;
            }
        }

        throw new TarifNotFound("$nom (soirée $idSoiree)");
    }
}
