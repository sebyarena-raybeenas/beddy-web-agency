<?php

namespace App\Filament\Support;

use App\Models\Domain;
use Closure;
use Illuminate\Database\Eloquent\Model;

class UniqueDomainName
{
    /**
     * Validation rule factory: the normalized name (same normalizer as the model) must not exist yet.
     * Pass it to TextInput::rule(); the current record is ignored when editing.
     */
    public static function rule(): Closure
    {
        return function (?Model $record): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
                if (! is_string($value) || trim($value) === '') {
                    return;
                }

                $exists = Domain::query()
                    ->where('name', Domain::normalizeName($value))
                    ->when($record?->exists, fn ($q) => $q->whereKeyNot($record->getKey()))
                    ->exists();

                if ($exists) {
                    $fail('Questo dominio è già registrato');
                }
            };
        };
    }
}
