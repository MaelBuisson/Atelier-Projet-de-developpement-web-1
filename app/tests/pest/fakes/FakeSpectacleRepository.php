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

    /**
     * Même contrat que PgSpectacleRepository::findAll() : style comparé sans tenir compte
     * de la casse, date comparée à celle de la soirée du spectacle.
     */
    public function findAll(?string $style = null, ?string $date = null): array
    {
        $tous = array_merge(...array_values($this->parSoiree));

        return array_values(array_filter(
            $tous,
            fn (Spectacle $s) => ($style === null || mb_strtolower($s->getStyleMusique()) === mb_strtolower($style))
                && ($date === null || $s->getSoiree()?->getDate()->format('Y-m-d') === $date)
        ));
    }

    public function getBySoiree(string $id): array
    {
        // une soirée sans spectacle donne un tableau vide, comme PgSpectacleRepository
        return $this->parSoiree[$id] ?? [];
    }
}
