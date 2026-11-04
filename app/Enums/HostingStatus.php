<?php

namespace App\Enums;

enum HostingStatus: string
{
    case Suo = 'suo';
    case Nostro = 'nostro';
    case DaAttivare = 'da_attivare';

    public function label(): string
    {
        return match ($this) {
            self::Suo => 'Suo',
            self::Nostro => 'Nostro',
            self::DaAttivare => 'Da attivare',
        };
    }
}
