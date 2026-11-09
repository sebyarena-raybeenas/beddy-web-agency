@props(['project'])
@php
    use App\Filament\Pages\ProjectsBoard;

    $overdue = $project->isOverdue();
    $lateMaterial = $project->hasLateMaterial();
    $doneReqs = $project->requirements->where('is_done', true)->count();
    $tasksTotal = $project->tasks_count ?? 0;
    $tasksDone = $project->tasks_done_count ?? 0;
    $pct = $tasksTotal > 0 ? (int) round($tasksDone / $tasksTotal * 100) : 0;
@endphp
<a href="{{ $project->detailUrl() }}" class="pb-card pb-pc {{ $project->markClasses() }}">
    <div class="pb-hd">
        <div class="pb-tt" style="border-color:{{ ProjectsBoard::typeColor($project->type) }}">
            <h3>{{ $project->name }}</h3>
            <div class="pb-cl">{{ $project->client->name }}</div>
            <span class="pb-tag pb-type" style="background:color-mix(in srgb, {{ ProjectsBoard::typeColor($project->type) }} 13%, transparent);color:{{ ProjectsBoard::typeColor($project->type) }}">{{ $project->type->label() }}</span>
        </div>
        <span class="pb-pill {{ ProjectsBoard::pill($project->status) }}">{{ $project->statusLabel() }}</span>
    </div>
    <div class="pb-mid">
        <span @class(['pb-warn' => $overdue])>📅 Scadenza: {{ $project->due_date?->format('d/m/Y') ?? '—' }}</span>
        @if ($lateMaterial)
            <span class="pb-warn">⚠ materiale in ritardo</span>
        @endif
        <span class="pb-prio {{ $project->priority->pill() }}" title="Priorità">{{ $project->priority->icon() }} {{ $project->priority->label() }}</span>
        <span class="pb-pay {{ $project->payment_status->pill() }}" title="Pagamento">💶 {{ $project->payment_status->label() }}</span>
    </div>
    <div class="pb-mid" style="margin:4px 0 6px"><span>Avanzamento to-do</span><b style="color:var(--ink)">{{ $pct }}%</b></div>
    <div class="pb-prog"><i style="width:{{ $pct }}%"></i></div>
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
        <div class="pb-meta"><span>☑ {{ $tasksDone }}/{{ $tasksTotal }} to-do</span><span>✓ {{ $doneReqs }}/{{ $project->requirements->count() }} requisiti</span></div>
    </div>
    <div class="pb-ft">
        <div><span>Dominio / hosting</span><b>{{ $project->primaryDomain?->name ?? '—' }} · {{ $project->hosting_status?->label() ?? '—' }}</b></div>
        <div><span>Origine</span><b><span class="pb-tag">{{ $project->origin->label() }}</span></b></div>
        <div><span>Commerciale</span><b>{{ $project->salesRep?->name ?? '—' }}</b></div>
        <div><span>Responsabile</span><b>{{ $project->owner?->name ?? 'Nessun responsabile' }}</b></div>
    </div>
</a>
