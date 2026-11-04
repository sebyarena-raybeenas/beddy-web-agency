@props(['project'])
@php
    $reqs = $project->requirements;
    $done = $reqs->where('is_done', true)->count();
@endphp
<div class="pb-reqs" title="{{ $done }}/{{ $reqs->count() }} requisiti">
    @foreach ($reqs as $req)
        <b @class(['ok' => $req->is_done]) title="{{ $req->label }}"></b>
    @endforeach
</div>
