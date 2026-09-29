<?php

declare(strict_types=1);

namespace App\School\Domain;

enum SchoolType: string
{
    case Liceum = 'liceum';
    case Technikum = 'technikum';

    public static function fromNormalizedToken(string $token): ?self
    {
        return match ($token) {
            'lo' => self::Liceum,
            'technikum' => self::Technikum,
            default => null,
        };
    }
}
