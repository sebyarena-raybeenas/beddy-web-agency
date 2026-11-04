<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MaterialKind: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }
}
