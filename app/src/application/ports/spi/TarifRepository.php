<?php

namespace nrv\application\ports\spi;

use nrv\domain\entities\Tarif;

/**
 * Port de sortie (SPI) : accès aux tarifs, indépendant de la base de données utilisée.
 */
interface TarifRepository
{
    /**
     * @return Tarif[] les tarifs de la soirée (normal, réduit), tableau vide si elle n'en a aucun
     */
    public function findBySoiree(string $idSoiree): array;

    /**
     * @return Tarif le tarif de la soirée portant ce nom ('normal' ou 'reduit'), avec son prix en base
     * @throws \nrv\domain\exceptions\TarifNotFound si la soirée n'a pas ce tarif
     */
    public function findBySoireeNom(string $idSoiree, string $nom): Tarif;
}