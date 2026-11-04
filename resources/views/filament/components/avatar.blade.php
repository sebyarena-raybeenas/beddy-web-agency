@props(['user' => null, 'size' => 28])
@if ($user)
    <span class="pb-av" title="{{ $user->name }}" style="background:{{ \App\Filament\Pages\ProjectsBoard::avatarColor($user->id) }};width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * 0.4) }}px">{{ \App\Filament\Pages\ProjectsBoard::initials($user->name) }}</span>
@endif
