<?php

namespace App\Models;

use App\Enums\MaterialKind;
use App\Enums\MaterialStatus;
use App\Observers\MaterialObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['project_id', 'requirement_id', 'kind', 'status', 'requested_at', 'received_at'])]
#[ObservedBy(MaterialObserver::class)]
class Material extends Model
{
    protected $attributes = [
        'status' => 'atteso',
    ];

    protected function casts(): array
    {
        return [
            'kind' => MaterialKind::class,
            'status' => MaterialStatus::class,
            'requested_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(ProjectRequirement::class, 'requirement_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
