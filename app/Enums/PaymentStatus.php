<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel
{
    case NonPagato = 'non_pagato';
    case Acconto = 'acconto';
    case Saldato = 'saldato';

    public function label(): string
    {
        return match ($this) {
            self::NonPagato => 'Non pagato',
            self::Acconto => 'Acconto pagato',
            self::Saldato => 'Saldato',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }

    /** Pill class shared by the board and the detail page. */
    public function pill(): string
    {
        return match ($this) {
            self::NonPagato => 'pay-no',
            self::Acconto => 'pay-acc',
            self::Saldato => 'pay-ok',
        };
    }

    /** Development can start once at least the deposit is paid. */
    public function allowsDevelopment(): bool
    {
        return $this !== self::NonPagato;
    }

    /** Publishing waits for the balance. */
    public function allowsPublishing(): bool
    {
        return $this === self::Saldato;
    }
}
