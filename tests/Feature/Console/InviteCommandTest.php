<?php

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Notification;

it('creates the user and sends the invitation', function () {
    Notification::fake();

    $this->artisan('beddy:invite', ['name' => 'Mario Rossi', 'email' => 'mario@example.test'])
        ->expectsOutputToContain('Invito inviato a mario@example.test')
        ->assertSuccessful();

    $user = User::where('email', 'mario@example.test')->firstOrFail();
    expect($user->name)->toBe('Mario Rossi');
    Notification::assertSentTo($user, UserInvitation::class);
});

it('fails with an Italian message when the email already exists', function () {
    Notification::fake();
    User::factory()->create(['email' => 'mario@example.test']);

    $this->artisan('beddy:invite', ['name' => 'Mario Rossi', 'email' => 'mario@example.test'])
        ->expectsOutputToContain('Invito non inviato')
        ->assertFailed();

    Notification::assertNothingSent();
});

it('fails on an invalid email', function () {
    Notification::fake();

    $this->artisan('beddy:invite', ['name' => 'Mario Rossi', 'email' => 'non-valida'])
        ->assertFailed();

    expect(User::where('name', 'Mario Rossi')->exists())->toBeFalse();
});
