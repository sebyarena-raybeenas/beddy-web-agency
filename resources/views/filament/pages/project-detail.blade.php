@php
    use App\Filament\Pages\ProjectsBoard;

    $statusPill = ProjectsBoard::pill($project->status);
    $doneReqs = $project->requirements->where('is_done', true)->count();
    $totalReqs = $project->requirements->count();
    $trigger = $project->workflow()->checklistTrigger();
@endphp
<x-filament-panels::page>
    <div class="pd">
        <div class="pd-crumb"><a href="{{ \App\Filament\Resources\Projects\ProjectResource::getUrl('index') }}">Progetti</a> / {{ $project->client->name }} — {{ $project->name }}</div>

        @if ($alert)
            <div class="pd-alert">ℹ {{ $alert }} Scadenza: <b>{{ $project->due_date->format('d/m/Y') }}</b></div>
        @endif
        @if ($paymentWarning)
            <div class="pd-alert pd-alert-pay">💶 {{ $paymentWarning }}</div>
        @endif

        <div class="pd-card pd-hero">
            <div class="pd-cover"><div class="pd-shot"><div class="b1"></div><div class="b2"></div><div class="b3"><i></i><i></i><i></i></div></div></div>
            <div class="pd-hbody {{ $project->markClasses() }}">
                <h2>{{ $project->name }}</h2>
                <div class="pd-by">Cliente <a href="{{ $clientUrl }}" class="pd-lnk">{{ $project->client->name }}</a> · creato{{ $project->creator ? ' da '.$project->creator->name : '' }} il {{ $project->created_at->format('d/m/Y') }} · <span class="pd-tag">{{ $project->origin->label() }}</span></div>
                <div class="pd-hact">
                    <span class="pb-pill {{ $statusPill }}">{{ $project->statusLabel() }}</span>
                    {{ $this->changeStatusAction }}
                    <a href="{{ $editUrl }}" class="pd-btn pd-ghost">✎ Modifica</a>
                    <div class="pd-grow"></div>
                    <div class="pd-avs">
                        @foreach ($people as $person)
                            <x-beddy::avatar :user="$person" :size="32" />
                        @endforeach
                    </div>
                </div>
                <div class="pd-hmeta">
                    <div><span>Tipo</span><b>{{ $project->type->label() }}</b></div>
                    <div><span>Responsabile</span><b>{{ $project->owner?->name ?? 'Nessun responsabile' }}</b></div>
                    <div>
                        <span>Scadenza</span>
                        <b @style(['color:var(--red)' => $overdue, 'color:var(--amb)' => ! $overdue && $dueDays !== null && $dueDays <= 7])>
                            @if ($project->due_date)
                                {{ $project->due_date->format('d/m/Y') }}@if ($dueDays !== null && ! $project->workflow()->isFinal($project->status)) · {{ $dueDays < 0 ? 'scaduta da '.abs($dueDays).' gg' : $dueDays.' gg rimasti' }}@endif
                            @else
                                —
                            @endif
                        </b>
                    </div>
                    <div><span>Avanzamento to-do</span><b>{{ $completedCount }} / {{ $tasksTotal }}</b><div class="pb-prog" style="margin-top:6px"><i style="width:{{ $tasksPct }}%"></i></div></div>
                </div>
            </div>
        </div>

        <x-beddy::status-stepper :states="$states" :current="$project->status" :ready="$ready" />

        <div class="pd-grid">
            <div>
                {{-- Requisiti --}}
                <div class="pd-card pd-box">
                    <h3>Requisiti obbligatori <span @class(['pb-pill', 'p-ready' => $totalReqs > 0 && $doneReqs === $totalReqs, 'p-prep' => $doneReqs < $totalReqs || $totalReqs === 0])>{{ $doneReqs }} / {{ $totalReqs }} completati</span><span class="pd-grow"></span><small>servono per «{{ $states[$ready] }}»</small></h3>
                    <x-beddy::requirement-ticks :project="$project" />
                    @forelse ($project->requirements as $req)
                        <div class="pd-chk">
                            <button type="button" wire:click="toggleRequirement({{ $req->id }})" @class(['pd-cb', 'ok' => $req->is_done]) title="{{ $req->is_done ? 'Segna come da fare' : 'Segna come completato' }}">{{ $req->is_done ? '✓' : '' }}</button>
                            <div class="pd-t">{{ $req->label }}@unless ($req->is_mandatory)<small>facoltativo</small>@endunless</div>
                            <div class="pd-r">
                                @if ($req->is_done && $req->done_at)
                                    {{ $req->done_at->format('d/m') }}
                                    <x-beddy::avatar :user="$req->doneBy" :size="24" />
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="pd-empty">Nessun requisito</div>
                    @endforelse
                </div>

                {{-- Materiale --}}
                <div class="pd-card pd-box">
                    <h3>Materiale<span class="pd-grow"></span><small>{{ $project->materials->count() }} voci</small></h3>
                    @forelse ($project->materials as $material)
                        <div class="pd-chk pd-mat">
                            <div class="pd-t">
                                {{ $material->kind->label() }}
                                <small>{{ $material->attachments->count() }} file{{ $material->requirement ? ' · '.$material->requirement->label : '' }}</small>
                                @foreach ($material->attachments as $att)
                                    <a href="{{ route('attachments.download', $att) }}" class="pd-file" title="Scarica">📎 {{ $att->filename }}</a>
                                @endforeach
                            </div>
                            <div class="pd-ms">
                                @foreach ($materialStatuses as $st)
                                    <button type="button" wire:click="setMaterialStatus({{ $material->id }}, '{{ $st->value }}')" @class(['on' => $material->status === $st, 'pd-ms-'.$st->value])>{{ $st->label() }}</button>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="pd-empty">Nessun materiale richiesto</div>
                    @endforelse
                </div>

                {{-- To-do --}}
                <div class="pd-card pd-box">
                    <h3>To-do <span @class(['pb-pill', 'p-ready' => $tasksTotal > 0 && $openCount === 0, 'p-prep' => $openCount > 0 || $tasksTotal === 0])>{{ $completedCount }} / {{ $tasksTotal }} completati</span><span class="pd-grow"></span><small>{{ $openCount }} da fare</small></h3>

                    @if ($checklist->isNotEmpty())
                        <div class="pd-group">Messa online <small>{{ $checklist->whereNull('completed_at')->count() }} da fare su {{ $checklist->count() }}</small></div>
                        @foreach ($checklist as $task)
                            @include('filament.pages.partials.project-task', ['task' => $task])
                        @endforeach
                        @if ($shown->isNotEmpty())
                            <div class="pd-group">Altri to-do</div>
                        @endif
                    @endif

                    @foreach ($shown as $task)
                        @include('filament.pages.partials.project-task', ['task' => $task])
                    @endforeach

                    @if ($shown->isEmpty() && $checklist->isEmpty())
                        <div class="pd-empty">Nessun to-do</div>
                    @endif

                    <div class="pd-add" x-data="{ title: '', assignee: '', due: '' }">
                        <input type="text" x-model="title" placeholder="Nuovo to-do…" maxlength="255" @keydown.enter.prevent="$wire.addTask(title, assignee ? parseInt(assignee) : null, due || null).then(() => { title = ''; due = '' })">
                        <select x-model="assignee">
                            <option value="">Nessun assegnatario</option>
                            @foreach ($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input type="date" x-model="due" title="Scadenza">
                        <button type="button" class="pd-btn pd-soft" @click="$wire.addTask(title, assignee ? parseInt(assignee) : null, due || null).then(() => { title = ''; due = '' })">＋ Aggiungi to-do</button>
                    </div>
                </div>

                {{-- Discussione --}}
                <div class="pd-card pd-box">
                    <h3>Discussione del team</h3>
                    <div class="pd-input" x-data="{ body: '' }">
                        <textarea x-model="body" rows="1" placeholder="Scrivi un commento…" maxlength="5000"></textarea>
                        <button type="button" class="pd-btn pd-pri" style="padding:7px 14px" @click="$wire.addComment(body).then(() => body = '')">Invia</button>
                    </div>
                    @forelse ($project->comments as $comment)
                        <div class="pd-cmt">
                            <x-beddy::avatar :user="$comment->user" :size="32" />
                            <div>
                                <b>{{ $comment->user?->name ?? 'Utente' }}</b><small>{{ $comment->created_at->locale('it')->diffForHumans() }}</small>
                                <p>{!! nl2br(e($comment->body)) !!}</p>
                            </div>
                        </div>
                    @empty
                        <div class="pd-empty">Nessun commento</div>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="pd-card pd-box">
                    <h3>Pagamento e servizi</h3>
                    <div class="pd-pay">
                        @foreach ($paymentStatuses as $ps)
                            <button type="button" wire:click="setPaymentStatus('{{ $ps->value }}')" @class(['on' => $project->payment_status === $ps, $ps->pill()])>{{ $ps->label() }}</button>
                        @endforeach
                    </div>
                    <div class="pd-kv"><span>Assistenza</span><b @style(['color:var(--grn)' => $project->has_assistance])>{{ $project->has_assistance ? 'Sì'.($project->assistance_type ? ', '.$project->assistance_type : '') : 'No' }}</b></div>
                    <div class="pd-kv"><span>Hosting</span><b>{{ $project->hosting_status?->label() ?? '—' }}</b></div>
                    <div class="pd-kv"><span>Dominio</span><b>{{ $project->primaryDomain ? $project->primaryDomain->name.($project->primaryDomain->ownership ? ' · '.$project->primaryDomain->ownership->label() : '') : '—' }}</b></div>
                </div>

                <div class="pd-card pd-box">
                    <h3>Cliente</h3>
                    <div class="pd-kv"><span>Referente</span><b>{{ $contact?->name ?? '—' }}</b></div>
                    <div class="pd-kv"><span>Telefoni</span><b>@forelse (array_filter($contact?->phones ?? []) as $phone){{ $phone }}@unless ($loop->last)<br>@endunless @empty — @endforelse</b></div>
                    <div class="pd-kv"><span>Email</span><b>@forelse (array_filter($contact?->emails ?? []) as $email){{ $email }}@unless ($loop->last)<br>@endunless @empty — @endforelse</b></div>
                </div>

                <div class="pd-card pd-box">
                    <h3>Attività recente</h3>
                    <div class="pd-tl">
                        @forelse ($timeline as $line)
                            <div class="pd-tli">{!! $line['html'] !!}<small>{{ $line['at'] }}</small></div>
                        @empty
                            <div class="pd-empty">Nessuna attività</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
