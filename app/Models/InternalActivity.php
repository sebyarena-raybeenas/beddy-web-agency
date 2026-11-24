<?php

namespace App\Models;

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use Database\Factories\InternalActivityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** An internal job not tied to a client project (software fixes, server maintenance…). */
#[Fillable(['name', 'owner_id', 'due_date', 'description', 'completed_at', 'created_by'])]
class InternalActivity extends Model
{
    /** @use HasFactory<InternalActivityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Notes live in the shared comments table: remove them with the activity.
        static::deleting(fn (InternalActivity $activity) => $activity->notes()->delete());
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function todos(): HasMany
    {
        return $this->hasMany(InternalActivityTodo::class)->orderBy('sort')->orderBy('id');
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->orderByDesc('id');
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isCompleted() && $this->due_date !== null && $this->due_date->isBefore(today());
    }

    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    public function detailUrl(): string
    {
        return InternalActivityResource::getUrl('view', ['record' => $this]);
    }
}
