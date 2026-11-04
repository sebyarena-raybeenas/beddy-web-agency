<?php

namespace App\Filament\Pages;

use App\Domain\Workflow\WorkflowRegistry;
use App\Enums\ProjectType;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ProjectsBoard extends Page
{
    protected static ?string $slug = '/';

    protected static ?string $title = 'Progetti';

    protected ?string $heading = '';

    protected static ?string $navigationLabel = 'Progetti';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected string $view = 'filament.pages.projects-board';

    /** grid | kanban | list (`$view` is taken by Filament's page view name). */
    #[Url(as: 'view')]
    public string $viewMode = 'grid';

    #[Url]
    public ?string $status = null;

    #[Url]
    public ?int $owner = null;

    #[Url]
    public ?string $type = null;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $dueSoon = false;

    #[Url]
    public bool $lateMaterial = false;

    /** Flow shown by the kanban view. */
    #[Url]
    public string $flow = 'sito';

    public function toggleMine(): void
    {
        $this->owner = $this->owner === auth()->id() ? null : auth()->id();
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status === $this->status ? null : $status;
    }

    /**
     * Counters, one per label (grouped across flows), preceded by the "Tutti" entry (id null).
     *
     * @return array<int, array{id: ?string, label: string, count: int, color: string, pill: string, pairs: array}>
     */
    public function counters(): array
    {
        $groups = self::groups();

        $rows = $this->baseQuery()->reorder()
            ->selectRaw('type, status, count(*) as total')
            ->groupBy('type', 'status')
            ->get();

        $total = 0;
        foreach ($rows as $row) {
            $total += $row->total;
            $flow = $row->type->flow();
            foreach ($groups as $id => $group) {
                if (in_array([$flow, $row->status], $group['pairs'], true)) {
                    $groups[$id]['count'] += $row->total;
                }
            }
        }

        return array_merge(
            [['id' => null, 'label' => 'Tutti i progetti', 'count' => $total, 'color' => 'var(--acc)', 'pill' => '', 'pairs' => []]],
            array_values($groups),
        );
    }

    /**
     * @return array<string, array{id: string, label: string, count: int, color: string, pill: string, pairs: array}>
     */
    public static function groups(): array
    {
        $registry = app(WorkflowRegistry::class);
        $byLabel = [];

        foreach (['sito', 'modifica'] as $flow) {
            foreach ($registry->get($flow)->states() as $key => $label) {
                $byLabel[$label][] = [$flow, $key];
            }
        }

        $groups = [];
        foreach ($byLabel as $label => $pairs) {
            $id = $pairs[0][1];
            if (isset($groups[$id])) {
                $id = $pairs[0][0].':'.$id;
            }
            $groups[$id] = [
                'id' => $id,
                'label' => $label,
                'count' => 0,
                'color' => self::stateColor($pairs[0][1]),
                'pill' => self::pill($pairs[0][1]),
                'pairs' => $pairs,
            ];
        }

        return $groups;
    }

    public static function pill(string $state): string
    {
        return match ($state) {
            'pronto_da_sviluppare', 'pronto_da_modificare' => 'p-ready',
            'in_sviluppo', 'in_modifica' => 'p-dev',
            'in_attesa_feedback' => 'p-feed',
            'feedback_modifiche', 'feedback_altre_modifiche' => 'p-mod',
            'in_pubblicazione' => 'p-pub',
            'pubblicato', 'modifiche_pubblicate' => 'p-live',
            default => 'p-prep',
        };
    }

    public static function stateColor(string $state): string
    {
        return match (self::pill($state)) {
            'p-ready' => 'var(--grn)',
            'p-dev' => 'var(--blu)',
            'p-feed' => 'var(--amb)',
            'p-mod' => 'var(--red)',
            'p-pub' => 'var(--vio)',
            'p-live' => 'var(--tea)',
            default => 'var(--gry)',
        };
    }

    public static function typeColor(ProjectType $type): string
    {
        return match ($type) {
            ProjectType::Nuovo => 'var(--acc)',
            ProjectType::Modifica => 'var(--tea)',
            ProjectType::Rifacimento => 'var(--amb)',
        };
    }

    public static function avatarColor(int $id): string
    {
        return ['#6c4ce0', '#1aa37a', '#f08c00', '#2f80ed', '#e03e6b', '#0ea5b7'][$id % 6];
    }

    public static function initials(string $name): string
    {
        return mb_strtoupper(collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    }

    public function createUrl(): string
    {
        return ProjectResource::getUrl('create');
    }

    protected function getViewData(): array
    {
        $projects = $this->projects();

        $columns = [];
        if ($this->viewMode === 'kanban') {
            foreach (app(WorkflowRegistry::class)->get($this->flowKey())->states() as $key => $label) {
                $columns[] = [
                    'key' => $key,
                    'label' => $label,
                    'pill' => self::pill($key),
                    'color' => self::stateColor($key),
                    'projects' => $projects->where('status', $key)->values(),
                ];
            }
        }

        return [
            'counters' => $this->counters(),
            'projects' => $projects,
            'columns' => $columns,
            'owners' => User::query()->orderBy('name')->pluck('name', 'id'),
            'types' => ProjectType::cases(),
        ];
    }

    private function flowKey(): string
    {
        return in_array($this->flow, ['sito', 'modifica'], true) ? $this->flow : 'sito';
    }

    /**
     * @return Collection<int, Project>
     */
    private function projects(): Collection
    {
        $query = $this->baseQuery();

        if ($this->status !== null && ($group = self::groups()[$this->status] ?? null)) {
            $query->where(function (Builder $q) use ($group) {
                foreach ($group['pairs'] as [$flow, $key]) {
                    $q->orWhere(fn (Builder $w) => $w->whereIn('type', self::typesOf($flow))->where('status', $key));
                }
            });
        }

        if ($this->viewMode === 'kanban') {
            $query->whereIn('type', self::typesOf($this->flowKey()));
        }

        return $query
            ->with(['client', 'owner', 'requirements', 'materials'])
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('name')
            ->get();
    }

    /** All filters except the status counter. */
    private function baseQuery(): Builder
    {
        return Project::query()
            ->when($this->owner, fn (Builder $q) => $q->where('owner_id', $this->owner))
            ->when($this->type, fn (Builder $q) => $q->where('type', $this->type))
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('name', 'like', $term)
                    ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', $term)));
            })
            ->when($this->dueSoon, fn (Builder $q) => $q->dueSoon())
            ->when($this->lateMaterial, fn (Builder $q) => $q->materialOverdue());
    }

    /** @return array<int, ProjectType> */
    private static function typesOf(string $flow): array
    {
        return array_values(array_filter(ProjectType::cases(), fn (ProjectType $t) => $t->flow() === $flow));
    }
}
