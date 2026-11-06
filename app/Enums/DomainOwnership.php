<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DomainOwnership: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }
}
