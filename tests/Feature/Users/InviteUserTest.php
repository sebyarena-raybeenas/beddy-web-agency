<?php

use App\Domain\Users\InviteUser;
use App\Models\User;
use App\Notifications\UserInvitation;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

it('creates the user and emails an invitation', function () {
    Notification::fake();

    $user = app(InviteUser::class)->handle('Utente Demo', 'demo@example.test');

    expect($user->exists)->toBeTrue()
        ->and($user->invited_at)->not->toBeNull()
        ->and($user->is_active)->toBeTrue();

    $this->assertDatabaseHas('users', ['email' => 'demo@example.test', 'name' => 'Utente Demo']);
    Notification::assertSentTo($user, UserInvitation::class);
});

it('links the invitation to the password reset page', function () {
    Notification::fake();

    $user = app(InviteUser::class)->handle('Utente Demo', 'demo@example.test');

    Notification::assertSentTo($user, UserInvitation::class, function (UserInvitation $n) use ($user) {
        $mail = $n->toMail($user);

        return $mail->subject === 'Sei stato invitato su Beddy Web Agency'
            && $mail->actionText === 'Imposta la password'
            && str_contains($mail->actionUrl, 'reset')
            && str_contains($mail->actionUrl, urlencode('demo@example.test'));
    });
});

it('rejects an email that already exists', function () {
    User::factory()->create(['email' => 'demo@example.test']);

    app(InviteUser::class)->handle('Altro', 'demo@example.test');
})->throws(ValidationException::class);

it('denies panel access to inactive users', function () {
    $user = User::factory()->create(['is_active' => false]);

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('records last login time on login', function () {
    $user = User::factory()->create();
    expect($user->last_login_at)->toBeNull();

    event(new Illuminate\Auth\Events\Login('web', $user, false));

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('invites a user from the Filament create page', function () {
    Notification::fake();
    $this->actingAs(User::factory()->create());

    Livewire\Livewire::test(App\Filament\Resources\Users\Pages\CreateUser::class)
        ->fillForm(['name' => 'Nuovo Utente', 'email' => 'nuovo@example.test'])
        ->call('create')
        ->assertHasNoFormErrors();

    Notification::assertSentTo(User::where('email', 'nuovo@example.test')->first(), UserInvitation::class);
});
