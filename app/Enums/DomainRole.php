<?php

namespace App\Enums;

enum DomainRole: string
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
}
