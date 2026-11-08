@php
    use App\Filament\Pages\ProjectsBoard;
@endphp
<x-filament-panels::page>
    <div class="pb">
        <div class="pb-ph">
            <h1>Clienti</h1>
            <div class="pb-sp"></div>
            <a href="{{ $this->createUrl() }}" class="pb-btn pb-pri">＋ Aggiungi cliente</a>
        </div>

        <div class="pb-tb">
            <input type="search" wire:model.live.debounce.300ms="search" placeholder="Cerca cliente o dominio…" class="pb-input">
        </div>

        <div class="cl-grid">
            @forelse ($clients as $client)
                @php
                    $byStatus = $client->projects->groupBy(fn ($p) => $p->statusLabel())->map->count();
                    $domains = $client->domains->pluck('name')->unique();
                    $contact = $client->contacts->first();
                @endphp
                <div class="pb-card cl-cc" wire:key="client-{{ $client->id }}">
                    <a href="{{ \App\Filament\Resources\Clients\ClientResource::getUrl('edit', ['record' => $client]) }}" class="cl-hd">
                        <div class="cl-lg" style="background:{{ ProjectsBoard::avatarColor($client->id) }}">{{ mb_strtoupper(mb_substr($client->name, 0, 1)) }}</div>
                        <div>
                            <h3>{{ $client->name }}</h3>
                            <span @class(['pb-tag', 'cl-asana' => $client->origin?->value === 'asana', 'cl-man' => $client->origin?->value !== 'asana'])>{{ $client->origin?->label() }}</span>
                        </div>
                    </a>
                    <div class="cl-kv"><span>Ragione sociale</span><b>{{ $client->legal_name ?: '—' }}</b></div>
                    <div class="cl-kv"><span>Referente</span><b>{{ $contact?->name ?? '—' }}</b></div>
                    <div class="cl-kv"><span>Progetti attivi</span><b>
                        <a href="{{ url('/') }}?search={{ urlencode($client->name) }}" class="cl-link">{{ $client->active_projects_count }}</a>
                    </b></div>
                    <div class="cl-kv"><span>Per stato</span><b>
                        @forelse ($byStatus as $label => $n)
                            <span class="cl-st">{{ $label }} {{ $n }}</span>
                        @empty
                            —
                        @endforelse
                    </b></div>
                    <div class="cl-kv"><span>Domini principali</span><b>{{ $domains->isEmpty() ? '—' : $domains->implode(', ') }}</b></div>
                </div>
            @empty
                <div class="pb-empty">Nessun cliente trovato.</div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
