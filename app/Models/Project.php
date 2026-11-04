<?php

namespace App\Models;

use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\Workflow;
use App\Domain\Workflow\WorkflowRegistry;
use App\Enums\Origin;
use App\Enums\ProjectType;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['client_id', 'name', 'type', 'status', 'owner_id', 'due_date', 'primary_domain_id', 'origin', 'created_by'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $attributes = [
        'origin' => 'manuale',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'origin' => Origin::class,
            'status_changed_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Project $project) {
            if (empty($project->status)) {
                $project->status = $project->workflow()->initial();
            }
            $project->status_changed_at = now();
        });

        static::created(function (Project $project) {
            $project->statusHistory()->create([
                'from_status' => null,
                'to_status' => $project->status,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
            ]);
        });

        static::updating(function (Project $project) {
            if ($project->isDirty('type')) {
                throw new InvalidTransition('Il tipo di progetto non si può cambiare dopo la creazione');
            }
        });
    }

    public function workflow(): Workflow
    {
        return app(WorkflowRegistry::class)->for($this->type);
    }

    public function statusLabel(): string
    {
        return $this->workflow()->label($this->status);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members');
    }

    public function primaryDomain(): BelongsTo
    {
        return $this->belongsTo(Domain::class, 'primary_domain_id');
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ProjectRequirement::class)->orderBy('sort');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(Material::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(ProjectStatusChange::class)->orderBy('id');
    }
}
