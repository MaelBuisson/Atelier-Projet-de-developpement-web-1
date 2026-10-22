<?php

declare(strict_types=1);

namespace nrv\domain\entities;

enum CommandeStatus: int
{
    case PANIER = 0;
    case VALIDEE = 1;
    case PAYEE = 2;
}