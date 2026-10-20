<?php

namespace nrv\domain\entities;

/**
 * Image illustrant un spectacle (un spectacle peut en avoir plusieurs).
 */
class Image
{
    private string $idImage;
    private string $url;

    public function __construct(string $id, string $url)
    {
        $this->idImage = $id;
        $this->url = $url;
    }

    // ── accesseurs ─
    public function getId(): string
    {
        return $this->idImage;
    }

    public function getUrl(): string
    {
        return $this->url;
    }
}
