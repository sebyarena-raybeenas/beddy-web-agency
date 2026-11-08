<?php

namespace App\Models;

use App\Enums\Origin;
use App\Models\Concerns\LogsModelActivity;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'legal_name', 'vat_number', 'address', 'origin', 'created_by'])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    use LogsModelActivity;

    protected $attributes = [
        'origin' => 'manuale',
    ];

    protected function casts(): array
    {
        return [
            'origin' => Origin::class,
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
