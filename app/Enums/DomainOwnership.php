<?php

namespace App\Enums;

enum DomainOwnership: string
{
    case Suo = 'suo';
    case Nostro = 'nostro';
    case DaAcquistare = 'da_acquistare';

    public function label(): string
    {
        return match ($this) {
            self::Suo => 'Suo',
            self::Nostro => 'Nostro',
            self::DaAcquistare => 'Da acquistare',
        };
    }
}
