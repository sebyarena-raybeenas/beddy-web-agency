@props(['states', 'current', 'ready'])
@php
    $keys = array_keys($states);
    $currentIndex = array_search($current, $keys, true);
@endphp
<div class="pd-card pd-flow">
    @foreach ($states as $key => $label)
        @php
            $i = array_search($key, $keys, true);
            $isCurrent = $key === $current;
            $isDone = $i < $currentIndex;
            $clickable = ! $isCurrent && $key !== $ready;
        @endphp
        @if ($clickable)
            <button type="button" wire:click="changeStatus('{{ $key }}')" title="Passa a «{{ $label }}»" @class(['pd-step', 'done' => $isDone, 'go'])><i>{{ $isDone ? '✓' : $i + 1 }}</i>{{ $label }}</button>
        @else
            <div @class(['pd-step', 'cur' => $isCurrent, 'done' => $isDone]) @if ($key === $ready) title="Impostato in automatico quando i requisiti sono completi" @endif><i>{{ $isDone ? '✓' : $i + 1 }}</i>{{ $label }}</div>
        @endif
    @endforeach
</div>
