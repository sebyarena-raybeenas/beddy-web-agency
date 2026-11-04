<?php

namespace App\Enums;

enum Origin: string
{
    case Asana = 'asana';
    case Manuale = 'manuale';

    public function label(): string
    {
        return match ($this) {
            self::Asana => 'Asana',
            self::Manuale => 'Manuale',
        };
    }
}
