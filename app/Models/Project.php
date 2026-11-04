<?php

namespace App\Models;

use App\Domain\Workflow\InvalidTransition;
use App\Domain\Workflow\Workflow;
use App\Domain\Workflow\WorkflowRegistry;
use App\Enums\MaterialStatus;
use App\Enums\Origin;
use App\Enums\ProjectType;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Concerns\LogsModelActivity;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

#[Fillable(['client_id', 'name', 'type', 'status', 'owner_id', 'due_date', 'primary_domain_id', 'origin', 'created_by'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    use LogsModelActivity;

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

    public function detailUrl(): string
    {
        return ProjectResource::getUrl('view', ['record' => $this]);
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

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')->orderByDesc('id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /** Not in a final state of its flow. */
    public function scopeNotFinal(Builder $query): void
    {
        $registry = app(WorkflowRegistry::class);

        foreach (['sito', 'modifica'] as $flow) {
            $workflow = $registry->get($flow);
            $finals = array_values(array_filter(array_keys($workflow->states()), fn ($s) => $workflow->isFinal($s)));
            $types = collect(ProjectType::cases())->filter(fn ($t) => $t->flow() === $flow)->all();

            $query->whereNot(fn (Builder $q) => $q->whereIn('type', $types)->whereIn('status', $finals));
        }
    }

    /** Due within the next 7 days (or already past) and not finished. */
    public function scopeDueSoon(Builder $query): void
    {
        $query->notFinal()->whereNotNull('due_date')->whereDate('due_date', '<=', today()->addDays(7));
    }

    /** Has a material still awaited and requested more than 7 days ago (same rule as the daily reminders). */
    public function scopeMaterialOverdue(Builder $query): void
    {
        $threshold = today()->subDays(7)->endOfDay();

        $query->whereHas('materials', fn (Builder $m) => $m
            ->where('status', MaterialStatus::Atteso->value)
            ->whereNotNull('requested_at')
            ->where('requested_at', '<=', $threshold));
    }

    protected function activityLogExcept(): array
    {
        return ['status', 'status_changed_at', 'due_date'];
    }

    /**
     * Activity of the project and of its requirements, materials and tasks,
     * merged with status changes, newest first.
     *
     * @return Collection<int, Activity|ProjectStatusChange>
     */
    public function timeline(): Collection
    {
        $subjects = [
            [$this->getMorphClass(), [$this->getKey()]],
            [(new ProjectRequirement)->getMorphClass(), $this->requirements()->reorder()->pluck('id')->all()],
            [(new Material)->getMorphClass(), $this->materials()->pluck('id')->all()],
            [(new Task)->getMorphClass(), $this->tasks()->pluck('id')->all()],
        ];

        $activities = Activity::query()
            ->where(function ($query) use ($subjects) {
                foreach ($subjects as [$type, $ids]) {
                    if ($ids !== []) {
                        $query->orWhere(fn ($q) => $q->where('subject_type', $type)->whereIn('subject_id', $ids));
                    }
                }
            })
            ->with('causer')
            ->get();

        return $activities
            ->toBase()
            ->concat($this->statusHistory()->reorder()->with('user')->get())
            ->sort(function ($a, $b) {
                $at = ($a instanceof Activity ? $a->created_at : $a->changed_at)->getTimestamp();
                $bt = ($b instanceof Activity ? $b->created_at : $b->changed_at)->getTimestamp();

                return [$bt, $b instanceof Activity ? 1 : 0, $b->getKey()] <=> [$at, $a instanceof Activity ? 1 : 0, $a->getKey()];
            })
            ->values();
    }
}
