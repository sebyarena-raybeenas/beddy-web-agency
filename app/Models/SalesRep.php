<?php

namespace App\Models;

use Database\Factories\SalesRepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A salesperson who brought in a project (not a user of the app). */
#[Fillable(['name', 'email', 'phone'])]
class SalesRep extends Model
{
    /** @use HasFactory<SalesRepFactory> */
    use HasFactory;

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
