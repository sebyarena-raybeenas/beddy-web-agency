@php
    use App\Filament\Pages\ProjectsBoard;
@endphp
<x-filament-panels::page>
    <div class="pb">
        <div class="pb-ph">
            <h1>Progetti</h1>
            <div class="pb-sp"></div>
            <a href="{{ $this->createUrl() }}" class="pb-btn pb-pri">＋ Aggiungi progetto</a>
        </div>

        <div class="pb-stats">
            @foreach ($counters as $c)
                <button type="button" wire:click="setStatus({{ $c['id'] === null ? 'null' : "'".$c['id']."'" }})" @class(['pb-card', 'pb-stat', 'on' => $status === $c['id']])>
                    <div class="n">{{ $c['count'] }}</div>
                    <div class="l">{{ $c['label'] }}</div>
                    <div class="bar" style="background:{{ $c['color'] }}"></div>
                </button>
            @endforeach
        </div>

        <div class="pb-tb">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cerca cliente o progetto…" class="pb-input">
            <select wire:model.live="type" class="pb-input">
                <option value="">Tipo: tutti</option>
                @foreach ($types as $t)
                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                @endforeach
            </select>
            <select wire:model.live="owner" class="pb-input">
                <option value="">Responsabile: tutti</option>
                @foreach ($owners as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="toggleMine" @class(['pb-chip', 'on' => $owner === auth()->id()])>👤 Assegnati a me</button>
            <button type="button" wire:click="$toggle('dueSoon')" @class(['pb-chip', 'on' => $dueSoon])>⏰ In scadenza</button>
            <button type="button" wire:click="$toggle('lateMaterial')" @class(['pb-chip', 'on' => $lateMaterial])>⚠ Materiale in ritardo</button>
            <div class="pb-seg">
                <span wire:click="$set('viewMode','grid')" @class(['on' => $viewMode === 'grid'])>▦ Griglia</span>
                <span wire:click="$set('viewMode','kanban')" @class(['on' => $viewMode === 'kanban'])>▥ Kanban</span>
                <span wire:click="$set('viewMode','list')" @class(['on' => $viewMode === 'list'])>☰ Lista</span>
            </div>
        </div>

        @if ($viewMode === 'kanban')
            <div class="pb-tb">
                <div class="pb-seg" style="margin-left:0">
                    <span wire:click="$set('flow','sito')" @class(['on' => $flow !== 'modifica'])>Siti</span>
                    <span wire:click="$set('flow','modifica')" @class(['on' => $flow === 'modifica'])>Modifiche</span>
                </div>
            </div>
            <div class="pb-kan" style="grid-template-columns:repeat({{ count($columns) }},minmax(230px,1fr))">
                @foreach ($columns as $col)
                    <div class="pb-col">
                        <h4><span class="pb-pill {{ $col['pill'] }}">{{ $col['label'] }}</span><em>{{ $col['projects']->count() }}</em></h4>
                        @foreach ($col['projects'] as $p)
                            <a href="{{ $p->detailUrl() }}" class="pb-card pb-kc" style="border-bottom-color:{{ $col['color'] }}">
                                <span class="pb-tag" style="background:color-mix(in srgb, {{ ProjectsBoard::typeColor($p->type) }} 13%, transparent);color:{{ ProjectsBoard::typeColor($p->type) }}">{{ $p->type->label() }}</span>
                                <h5>{{ $p->client->name }}</h5>
                                <p>{{ $p->name }}</p>
                                <x-beddy::requirement-ticks :project="$p" />
                                <div class="pb-row" style="margin-top:10px">
                                    @if ($p->owner)
                                        <x-beddy::avatar :user="$p->owner" />
                                    @else
                                        <span class="pb-noowner">Nessun responsabile</span>
                                    @endif
                                    <span class="pb-meta">📅 {{ $p->due_date?->format('d/m/Y') ?? '—' }}</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @elseif ($viewMode === 'list')
            <div class="pb-card" style="overflow-x:auto">
                <table class="pb-table">
                    <tr><th>Cliente / progetto</th><th>Tipo</th><th>Stato</th><th>Requisiti</th><th>To-do</th><th>Responsabile</th><th>Scadenza</th><th>Avvisi</th></tr>
                    @forelse ($projects as $p)
                        @php
                            $overdue = $p->isOverdue();
                            $late = $p->hasLateMaterial();
                            $pct = $p->tasks_count > 0 ? (int) round($p->tasks_done_count / $p->tasks_count * 100) : 0;
                        @endphp
                        <tr class="pb-tr">
                            <td><a href="{{ $p->detailUrl() }}"><b>{{ $p->client->name }}</b><div class="pb-sub">{{ $p->name }}</div></a></td>
                            <td>{{ $p->type->label() }}</td>
                            <td><span class="pb-pill {{ ProjectsBoard::pill($p->status) }}">{{ $p->statusLabel() }}</span></td>
                            <td style="min-width:120px"><x-beddy::requirement-ticks :project="$p" /></td>
                            <td><div class="pb-prog" style="width:90px" title="{{ $p->tasks_done_count }}/{{ $p->tasks_count }} to-do"><i style="width:{{ $pct }}%"></i></div></td>
                            <td>
                                @if ($p->owner)
                                    <span class="pb-owner"><x-beddy::avatar :user="$p->owner" /> {{ $p->owner->name }}</span>
                                @else
                                    <span class="pb-noowner">Nessun responsabile</span>
                                @endif
                            </td>
                            <td @class(['pb-warn' => $overdue])>{{ $p->due_date?->format('d/m/Y') ?? '—' }}</td>
                            <td>@if ($late)<span class="pb-warn">materiale in ritardo</span>@else — @endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="pb-empty">Nessun progetto trovato.</td></tr>
                    @endforelse
                </table>
            </div>
        @else
            <div class="pb-grid">
                @forelse ($projects as $p)
                    <x-beddy::project-card :project="$p" wire:key="p-{{ $p->id }}" />
                @empty
                    <div class="pb-empty">Nessun progetto trovato.</div>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
