<?php

use App\Filament\Resources\InternalActivities\InternalActivityResource;
use App\Filament\Resources\InternalActivities\Pages\ActivityDetail;
use App\Filament\Resources\InternalActivities\Pages\CreateInternalActivity;
use App\Filament\Resources\InternalActivities\Pages\ListInternalActivities;
use App\Models\Comment;
use App\Models\InternalActivity;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['name' => 'Mario Tecnico']);
    $this->actingAs($this->user);
});

function activityDetail(InternalActivity $activity)
{
    return Livewire::test(ActivityDetail::class, ['record' => $activity->getRouteKey()]);
}

it('creates an activity with owner, due date, description and initial to-dos', function () {
    Livewire::test(CreateInternalActivity::class)
        ->fillForm([
            'name' => 'Aggiornamento PHP server',
            'owner_id' => $this->user->id,
            'due_date' => '2026-12-15',
            'description' => 'Passare a PHP 8.4 su tutti i siti',
            'todos' => [['title' => 'Backup'], ['title' => 'Aggiornamento']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $activity = InternalActivity::firstOrFail();

    expect($activity->owner->is($this->user))->toBeTrue()
        ->and($activity->due_date->toDateString())->toBe('2026-12-15')
        ->and($activity->created_by)->toBe($this->user->id)
        ->and($activity->todos->pluck('title')->all())->toBe(['Backup', 'Aggiornamento']);
});

it('requires a name', function () {
    Livewire::test(CreateInternalActivity::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

it('lists open activities by default and hides completed ones', function () {
    $open = InternalActivity::factory()->create(['name' => 'Pulizia log']);
    $done = InternalActivity::factory()->create(['name' => 'Rinnovo certificati', 'completed_at' => now()]);

    $this->get(InternalActivityResource::getUrl('index'))->assertOk();

    Livewire::test(ListInternalActivities::class)
        ->assertCanSeeTableRecords([$open])
        ->assertCanNotSeeTableRecords([$done]);
});

it('manages to-dos: add, complete, reopen and delete', function () {
    $activity = InternalActivity::factory()->create();

    $page = activityDetail($activity)->call('addTodo', 'Controllare i backup')->call('addTodo', '   ');
    $todo = $activity->todos()->firstOrFail();

    expect($activity->todos()->count())->toBe(1);

    $page->call('toggleTodo', $todo->id)->assertSeeHtml('pd-done')->assertSee('1 / 1');
    expect($todo->fresh()->completed_at)->not->toBeNull();

    $page->call('toggleTodo', $todo->id);
    expect($todo->fresh()->completed_at)->toBeNull();

    $page->call('deleteTodo', $todo->id);
    expect($activity->todos()->count())->toBe(0);
});

it('refuses to touch a to-do of another activity', function () {
    $activity = InternalActivity::factory()->create();
    $other = InternalActivity::factory()->create();
    $foreign = $other->todos()->create(['title' => 'Altro', 'sort' => 1]);

    activityDetail($activity)->call('toggleTodo', $foreign->id)->assertStatus(404);
    expect($foreign->fresh()->completed_at)->toBeNull();
});

it('adds notes and lets only the author delete them', function () {
    $activity = InternalActivity::factory()->create();

    activityDetail($activity)->call('addNote', 'Fatto il backup completo')->assertSee('Fatto il backup completo');
    $mine = $activity->notes()->firstOrFail();

    $otherNote = $activity->notes()->create(['user_id' => User::factory()->create()->id, 'body' => 'Nota di un collega']);

    activityDetail($activity)->call('deleteNote', $otherNote->id)->call('deleteNote', $mine->id);

    expect(Comment::find($mine->id))->toBeNull()
        ->and(Comment::find($otherNote->id))->not->toBeNull();
});

it('marks the activity completed and reopens it', function () {
    $activity = InternalActivity::factory()->create();

    activityDetail($activity)->call('toggleCompleted')->assertSee('Riapri attività');
    expect($activity->fresh()->isCompleted())->toBeTrue();

    activityDetail($activity)->call('toggleCompleted');
    expect($activity->fresh()->isCompleted())->toBeFalse();
});

it('shows an overdue warning and removes notes with the activity', function () {
    $activity = InternalActivity::factory()->create(['due_date' => today()->subDays(3)]);
    $activity->notes()->create(['user_id' => $this->user->id, 'body' => 'x']);

    activityDetail($activity)->assertSee('Scadenza superata da 3 giorni');

    $activity->delete();
    expect(Comment::count())->toBe(0);
});
