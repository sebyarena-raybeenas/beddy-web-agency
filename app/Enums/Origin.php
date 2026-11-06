<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Origin: string implements HasLabel
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

    public function getLabel(): string
    {
        return $this->label();
    }
}
