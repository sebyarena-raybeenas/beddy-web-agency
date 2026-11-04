<?php

namespace App\Enums;

enum MaterialStatus: string
{
    case Atteso = 'atteso';
    case Ricevuto = 'ricevuto';
    case NonNecessario = 'non_necessario';

    public function label(): string
    {
        return match ($this) {
            self::Atteso => 'Atteso',
            self::Ricevuto => 'Ricevuto',
            self::NonNecessario => 'Non necessario',
        };
    }

    /** Whether the linked requirement counts as done. */
    public function isResolved(): bool
    {
        return $this !== self::Atteso;
    }
}
