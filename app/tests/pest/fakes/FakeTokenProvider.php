<?php

namespace tests\pest\fakes;

use nrv\application\ports\spi\TokenProvider;
use nrv\domain\entities\Utilisateur;

class FakeTokenProvider implements TokenProvider
{
    public function generer(Utilisateur $utilisateur): string
    {
        return 'token-' . $utilisateur->getId();
    }
}