@php($done = $task->completed_at !== null)
<div @class(['pd-chk', 'pd-done' => $done]) wire:key="task-{{ $task->id }}">
    <button type="button" wire:click="toggleTask({{ $task->id }})" @class(['pd-cb', 'ok' => $done]) title="{{ $done ? 'Ripristina come da fare' : 'Segna come completato' }}">{{ $done ? '✓' : '' }}</button>
    <div class="pd-t">{{ $task->title }}</div>
    <div class="pd-r">
        @if ($task->due_date)
            <span @style(['color:var(--red);font-weight:700' => ! $done && $task->due_date->isBefore(today())])>{{ $task->due_date->format('d/m') }}</span>
        @endif
        <x-beddy::avatar :user="$task->assignee" :size="24" />
    </div>
</div>
