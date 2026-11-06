<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DomainRole: string implements HasLabel
{
    case Principale = 'principale';
    case Secondario = 'secondario';

    public function label(): string
    {
        return match ($this) {
            self::Principale => 'Principale',
            self::Secondario => 'Secondario',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
