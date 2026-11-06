<?php

namespace App\Domain\Users;

use App\Models\User;

class DeactivationGuard
{
    /**
     * Italian reason why $user cannot be deactivated, or null when it can.
     */
    public static function reasonToKeepActive(User $user): ?string
    {
        if ($user->is(auth()->user())) {
            return 'Non puoi disattivare il tuo account';
        }

        $othersActive = User::query()->where('is_active', true)->whereKeyNot($user->getKey())->exists();

        return $othersActive ? null : 'Deve restare almeno un utente attivo';
    }

    /**
     * Validation closure for an `is_active` field/column.
     */
    public static function rule(User $user): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
            if (filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }

            if ($reason = self::reasonToKeepActive($user)) {
                $fail($reason);
            }
        };
    }
}
