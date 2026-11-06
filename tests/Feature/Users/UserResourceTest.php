<?php

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->me = User::factory()->create();
    $this->actingAs($this->me);
});

it('lives under the Italian utenti slug', function () {
    expect(UserResource::getUrl('index'))->toEndWith('/utenti');

    $this->get(UserResource::getUrl('index'))->assertOk();
});

it('does not let a user deactivate themself from the table', function () {
    $other = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->call('updateTableColumnState', 'is_active', (string) $this->me->id, false)
        ->assertReturned(fn ($r) => str_contains($r['error'] ?? '', 'Non puoi disattivare il tuo account'));

    expect($this->me->fresh()->is_active)->toBeTrue();
});

it('does not let the last active user be deactivated from the table', function () {
    $other = User::factory()->create(['is_active' => false]);
    // $this->me is the only active user; try to deactivate it through another actor path
    $actor = User::factory()->create(['is_active' => false]);
    $this->actingAs($actor);

    Livewire::test(ListUsers::class)
        ->call('updateTableColumnState', 'is_active', (string) $this->me->id, false)
        ->assertReturned(fn ($r) => str_contains($r['error'] ?? '', 'Deve restare almeno un utente attivo'));

    expect($this->me->fresh()->is_active)->toBeTrue();
});

it('deactivates another user when someone else stays active', function () {
    $other = User::factory()->create();

    Livewire::test(ListUsers::class)
        ->call('updateTableColumnState', 'is_active', (string) $other->id, false);

    expect($other->fresh()->is_active)->toBeFalse();
});

it('blocks self-deactivation in the edit form', function () {
    User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $this->me->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasFormErrors(['is_active']);

    expect($this->me->fresh()->is_active)->toBeTrue();
});

it('blocks deactivating the last active user in the edit form', function () {
    $actor = User::factory()->create(['is_active' => false]);
    $this->actingAs($actor);

    Livewire::test(EditUser::class, ['record' => $this->me->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasFormErrors(['is_active']);

    expect($this->me->fresh()->is_active)->toBeTrue();
});
