<?php

namespace App\Enums;

enum ProjectType: string
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
}
