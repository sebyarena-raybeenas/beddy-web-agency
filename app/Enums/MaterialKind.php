<?php

namespace App\Enums;

enum MaterialKind: string
{
    case Foto = 'foto';
    case Testi = 'testi';
    case Logo = 'logo';
    case Altro = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::Foto => 'Foto',
            self::Testi => 'Testi',
            self::Logo => 'Logo',
            self::Altro => 'Altro',
        };
    }
}
