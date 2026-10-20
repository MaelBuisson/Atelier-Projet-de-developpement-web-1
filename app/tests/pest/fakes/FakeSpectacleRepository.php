<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\SpectacleRepository;
use nrv\domain\entities\Spectacle;

/**
 * Faux repository des spectacles : les spectacles sont rangés en mémoire par soirée.
 */
class FakeSpectacleRepository implements SpectacleRepository
{
    /** @param array<string, Spectacle[]> $parSoiree id de soirée => ses spectacles */
    public function __construct(private array $parSoiree = [])
    {
    }

    public function findAll(): array
    {
        return array_merge(...array_values($this->parSoiree));
    }

    public function getBySoiree(string $id): array
    {
        // une soirée sans spectacle donne un tableau vide, comme PgSpectacleRepository
        return $this->parSoiree[$id] ?? [];
    }
}
