<?php

namespace nrv\domain\entities;

/**
 * Artiste (ou groupe) se produisant dans un ou plusieurs spectacles.
 * Le prénom est facultatif : un groupe n'a qu'un nom.
 */
class Artiste
{
    private string $idArtiste;
    private string $nom;
    private ?string $prenom;

    public function __construct(string $id, string $nom, ?string $prenom = null)
    {
        $this->idArtiste = $id;
        $this->nom = $nom;
        $this->prenom = $prenom;
    }

    public function getId(): string
    {
        return $this->idArtiste;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }
}
