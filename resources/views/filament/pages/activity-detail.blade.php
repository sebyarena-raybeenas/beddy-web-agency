<x-filament-panels::page>
    <div class="pd">
        <div class="pd-crumb"><a href="{{ $indexUrl }}">Attività interne</a> / {{ $activity->name }}</div>

        @if ($activity->isOverdue())
            <div class="pd-alert pd-alert-pay">⏰ Scadenza superata da {{ abs($dueDays) }} {{ abs($dueDays) === 1 ? 'giorno' : 'giorni' }}: completa l'attività o aggiorna la scadenza.</div>
        @endif

        <div class="pd-card pd-hero">
            <div @class(['pd-hbody', 'ad-done' => $activity->isCompleted()])>
                <h2>🔧 {{ $activity->name }}</h2>
                <div class="pd-by">Attività interna · creata{{ $activity->creator ? ' da '.$activity->creator->name : '' }} il {{ $activity->created_at->format('d/m/Y') }}</div>
                <div class="pd-hact" style="margin-top:14px">
                    <span @class(['pb-pill', 'p-ready' => $activity->isCompleted(), 'p-feed' => ! $activity->isCompleted()])>{{ $activity->isCompleted() ? 'Completata il '.$activity->completed_at->format('d/m/Y') : 'Aperta' }}</span>
                    <button type="button" wire:click="toggleCompleted" @class(['pd-btn', 'pd-pri' => ! $activity->isCompleted(), 'pd-ghost' => $activity->isCompleted()])>
                        {{ $activity->isCompleted() ? '↺ Riapri attività' : '✓ Segna come completata' }}
                    </button>
                    <a href="{{ $editUrl }}" class="pd-btn pd-ghost">✎ Modifica</a>
                </div>
                <div class="pd-hmeta ad-meta">
                    <div><span>Responsabile</span><b>{{ $activity->owner?->name ?? 'Nessun responsabile' }}</b></div>
                    <div>
                        <span>Scadenza</span>
                        <b @style(['color:var(--red)' => $activity->isOverdue(), 'color:var(--amb)' => ! $activity->isOverdue() && ! $activity->isCompleted() && $dueDays !== null && $dueDays <= 7])>
                            @if ($activity->due_date)
                                {{ $activity->due_date->format('d/m/Y') }}@if ($dueDays !== null && ! $activity->isCompleted()) · {{ $dueDays < 0 ? 'scaduta da '.abs($dueDays).' gg' : $dueDays.' gg rimasti' }}@endif
                            @else
                                —
                            @endif
                        </b>
                    </div>
                    <div><span>Avanzamento to-do</span><b>{{ $todosDone }} / {{ $todosTotal }}</b><div class="pb-prog" style="margin-top:6px"><i style="width:{{ $todosPct }}%"></i></div></div>
                </div>
            </div>
        </div>

        <div class="pd-grid">
            <div>
                {{-- To-do --}}
                <div class="pd-card pd-box">
                    <h3>To-do <span @class(['pb-pill', 'p-ready' => $todosTotal > 0 && $todosDone === $todosTotal, 'p-prep' => $todosDone < $todosTotal || $todosTotal === 0])>{{ $todosDone }} / {{ $todosTotal }} completati</span></h3>

                    @forelse ($activity->todos as $todo)
                        @php($done = $todo->completed_at !== null)
                        <div @class(['pd-chk', 'pd-done' => $done]) wire:key="todo-{{ $todo->id }}">
                            <button type="button" wire:click="toggleTodo({{ $todo->id }})" @class(['pd-cb', 'ok' => $done]) title="{{ $done ? 'Ripristina come da fare' : 'Segna come completato' }}">{{ $done ? '✓' : '' }}</button>
                            <div class="pd-t">{{ $todo->title }}</div>
                            <div class="pd-r">
                                @if ($done)
                                    <small>{{ $todo->completed_at->format('d/m') }}</small>
                                @endif
                                <button type="button" class="ad-del" wire:click="deleteTodo({{ $todo->id }})" wire:confirm="Eliminare questo to-do?" title="Elimina">✕</button>
                            </div>
                        </div>
                    @empty
                        <div class="pd-empty">Nessun to-do: aggiungi qui sotto le cose da fare.</div>
                    @endforelse

                    <div class="pd-add" x-data="{ title: '' }">
                        <input type="text" x-model="title" placeholder="Nuovo to-do…" maxlength="255" @keydown.enter.prevent="$wire.addTodo(title).then(() => title = '')">
                        <button type="button" class="pd-btn pd-soft" @click="$wire.addTodo(title).then(() => title = '')">＋ Aggiungi to-do</button>
                    </div>
                </div>

                {{-- Note --}}
                <div class="pd-card pd-box">
                    <h3>Note</h3>
                    <div class="pd-input" x-data="{ body: '' }">
                        <textarea x-model="body" rows="2" placeholder="Scrivi una nota…" maxlength="5000"></textarea>
                        <button type="button" class="pd-btn pd-pri" style="padding:7px 14px" @click="$wire.addNote(body).then(() => body = '')">Aggiungi</button>
                    </div>
                    @forelse ($activity->notes as $note)
                        <div class="pd-cmt" wire:key="note-{{ $note->id }}">
                            <x-beddy::avatar :user="$note->user" :size="32" />
                            <div style="flex:1">
                                <b>{{ $note->user?->name ?? 'Utente' }}</b><small>{{ $note->created_at->locale('it')->diffForHumans() }}</small>
                                <p>{!! nl2br(e($note->body)) !!}</p>
                            </div>
                            @if ($note->user_id === auth()->id())
                                <button type="button" class="ad-del" wire:click="deleteNote({{ $note->id }})" wire:confirm="Eliminare questa nota?" title="Elimina">✕</button>
                            @endif
                        </div>
                    @empty
                        <div class="pd-empty">Nessuna nota</div>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="pd-card pd-box">
                    <h3>Descrizione</h3>
                    @if (filled($activity->description))
                        <div class="ad-desc">{!! nl2br(e($activity->description)) !!}</div>
                    @else
                        <div class="pd-empty">Nessuna descrizione. <a href="{{ $editUrl }}" class="pd-lnk">Aggiungila</a></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
