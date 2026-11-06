<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectType: string implements HasLabel
{
    case Nuovo = 'nuovo';
    case Modifica = 'modifica';
    case Rifacimento = 'rifacimento';

    public function label(): string
    {
        return match ($this) {
            self::Nuovo => 'Nuovo sito',
            self::Modifica => 'Modifica',
            self::Rifacimento => 'Rifacimento',
        };
    }

    public function flow(): string
    {
        return match ($this) {
            self::Nuovo, self::Rifacimento => 'sito',
            self::Modifica => 'modifica',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
