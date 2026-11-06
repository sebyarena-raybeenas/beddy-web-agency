<?php

namespace App\Models;

use App\Enums\DomainOwnership;
use App\Enums\DomainRole;
use App\Models\Concerns\LogsModelActivity;
use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['client_id', 'project_id', 'name', 'role', 'ownership'])]
class Domain extends Model
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory;

    use LogsModelActivity;

    protected $attributes = [
        'role' => 'secondario',
    ];

    protected function casts(): array
    {
        return [
            'role' => DomainRole::class,
            'ownership' => DomainOwnership::class,
        ];
    }

    public static function normalizeName(string $value): string
    {
        $name = mb_strtolower(trim($value));
        $name = preg_replace('#^https?://#', '', $name);
        $name = preg_replace('#^www\.#', '', $name);

        return rtrim($name, '/');
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => static::normalizeName($value),
        );
    }

    protected static function booted(): void
    {
        static::saving(function (Domain $domain) {
            $exists = static::query()
                ->where('name', $domain->name)
                ->when($domain->exists, fn ($q) => $q->whereKeyNot($domain->getKey()))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'Questo dominio è già registrato',
                ]);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
