@props(['project'])
@php
    use App\Filament\Pages\ProjectsBoard;

    $workflow = $project->workflow();
    $final = $workflow->isFinal($project->status);
    $overdue = ! $final && $project->due_date && $project->due_date->isBefore(today());
    $threshold = today()->subDays(7)->endOfDay();
    $lateMaterial = $project->materials->contains(fn ($m) => $m->status === \App\Enums\MaterialStatus::Atteso && $m->requested_at && $m->requested_at->lte($threshold));
    $doneReqs = $project->requirements->where('is_done', true)->count();
@endphp
<a href="{{ $project->detailUrl() }}" class="pb-card pb-pc">
    <div class="pb-hd">
        <div class="pb-tt" style="border-color:{{ ProjectsBoard::typeColor($project->type) }}">
            <h3>{{ $project->client->name }}</h3>
            <div class="pb-cl">{{ $project->name }} · {{ $project->type->label() }}</div>
        </div>
        <span class="pb-pill {{ ProjectsBoard::pill($project->status) }}">{{ $project->statusLabel() }}</span>
    </div>
    <div class="pb-mid">
        <span @class(['pb-warn' => $overdue])>📅 Scadenza: {{ $project->due_date?->format('d/m/Y') ?? '—' }}</span>
        @if ($lateMaterial)
            <span class="pb-warn">⚠ materiale in ritardo</span>
        @endif
    </div>
    <x-beddy::requirement-ticks :project="$project" />
    <div class="pb-row">
        <div class="pb-owner">
            @if ($project->owner)
                <x-beddy::avatar :user="$project->owner" />
                <span>{{ $project->owner->name }}</span>
            @else
                <span class="pb-noowner">Nessun responsabile</span>
            @endif
        </div>
        <div class="pb-meta"><span>✓ {{ $doneReqs }}/{{ $project->requirements->count() }} requisiti</span></div>
    </div>
</a>
