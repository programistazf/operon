<?php

declare(strict_types=1);

namespace App\School\Domain;

enum AliasKind: string
{
    case Official = 'official';
    case Alias = 'alias';
}
