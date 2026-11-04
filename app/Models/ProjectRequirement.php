<?php

namespace App\Models;

use App\Observers\ProjectRequirementObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['project_id', 'template_id', 'label', 'is_mandatory', 'is_done', 'done_at', 'done_by', 'sort'])]
#[ObservedBy(ProjectRequirementObserver::class)]
class ProjectRequirement extends Model
{
    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'is_done' => 'boolean',
            'done_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(RequirementTemplate::class, 'template_id');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    public function material(): HasOne
    {
        return $this->hasOne(Material::class, 'requirement_id');
    }
}
