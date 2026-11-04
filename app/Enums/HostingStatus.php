<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum HostingStatus: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }
}
