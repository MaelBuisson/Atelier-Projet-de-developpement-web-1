<?php

namespace nrv\domain\entities;

class Image
{
    private string $idImage;
    private string $url;

    public function __construct(string $id, string $nom, string $url)
    {
        $this->idImage = $id;
        $this->url = $url;
    }
}