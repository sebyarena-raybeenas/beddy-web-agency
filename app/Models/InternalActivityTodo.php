<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['internal_activity_id', 'title', 'completed_at', 'sort', 'created_by'])]
class InternalActivityTodo extends Model
{
    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(InternalActivity::class, 'internal_activity_id');
    }

    public function toggle(): void
    {
        $this->forceFill(['completed_at' => $this->completed_at === null ? now() : null])->save();
    }
}
