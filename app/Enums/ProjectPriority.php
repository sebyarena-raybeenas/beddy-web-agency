<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProjectPriority: string implements HasLabel
{
    case Urgente = 'urgente';
    case Alta = 'alta';
    case Normale = 'normale';
    case Bassa = 'bassa';

    public function label(): string
    {
        return match ($this) {
            self::Urgente => 'Urgente',
            self::Alta => 'Alta',
            self::Normale => 'Normale',
            self::Bassa => 'Bassa',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /** Sort position: the most urgent first. */
    public function rank(): int
    {
        return match ($this) {
            self::Urgente => 0,
            self::Alta => 1,
            self::Normale => 2,
            self::Bassa => 3,
        };
    }

    public function pill(): string
    {
        return 'prio-'.$this->value;
    }

    public function icon(): string
    {
        return match ($this) {
            self::Urgente => '🔴',
            self::Alta => '🟠',
            self::Normale => '⚪',
            self::Bassa => '🔵',
        };
    }
}
