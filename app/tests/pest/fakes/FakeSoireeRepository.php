<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\SoireeRepository;
use nrv\domain\entities\Soiree;
use nrv\domain\exceptions\SoireeNotFound;

/**
 * Faux repository des soirées : les soirées sont gardées en mémoire, sans base de données.
 */
class FakeSoireeRepository implements SoireeRepository
{
    /** @var array<string, Soiree> soirées indexées par id */
    private array $soirees = [];

    /** @param Soiree[] $soirees */
    public function __construct(array $soirees = [])
    {
        foreach ($soirees as $soiree) {
            $this->soirees[$soiree->getId()] = $soiree;
        }
    }

    public function findById(string $id): Soiree
    {
        // même contrat que le vrai repository : exception si la soirée n'existe pas
        return $this->soirees[$id] ?? throw new SoireeNotFound($id);
    }
}
