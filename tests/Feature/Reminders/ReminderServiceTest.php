<?php

use App\Domain\Reminders\ReminderService;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\MaterialKind;
use App\Models\InternalActivity;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
use App\Notifications\ActivityDeadlineReminder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;

function day(string $date): CarbonImmutable
{
    Carbon::setTestNow(CarbonImmutable::parse($date.' 09:00', 'Europe/Rome'));

    return CarbonImmutable::today();
}

function runReminders(string $date): int
{
    return app(ReminderService::class)->run(day($date));
}

function ownedProject(string $status, array $attrs = []): Project
{
    return Project::factory()->create(array_merge([
        'owner_id' => User::factory()->create()->id,
        'name' => 'Hotel Rossi',
        'status' => $status,
    ], $attrs));
}

afterEach(fn () => Carbon::setTestNow());

it('reminds the owner every day while Pronto', function () {
    $project = ownedProject('pronto_da_sviluppare');

    expect(runReminders('2026-11-10'))->toBe(1)
        ->and(runReminders('2026-11-11'))->toBe(1)
        ->and(runReminders('2026-11-12'))->toBe(1)
        ->and($project->owner->notifications)->toHaveCount(3)
        ->and($project->owner->notifications->first()->data['body'])
        ->toBe('Il sito Hotel Rossi è pronto da sviluppare, puoi iniziare.')
        ->and($project->owner->notifications->first()->data['project_id'])->toBe($project->id)
        ->and($project->owner->notifications->first()->data['actions'][0]['url'])->toBe($project->detailUrl());
});

it('sends the deadline reminder from day 7 and repeats daily', function () {
    day('2026-11-10');
    $project = ownedProject('in_preparazione');
    app(ProjectStatusService::class)->change($project, 'in_sviluppo', null);

    expect(runReminders('2026-11-16'))->toBe(0)
        ->and(runReminders('2026-11-17'))->toBe(1)
        ->and(runReminders('2026-11-18'))->toBe(1)
        ->and($project->owner->notifications->first()->data['body'])
        ->toBe("Hai finito? Contatta il cliente per l'appuntamento. — Hotel Rossi");
});

it('stops after the status changes', function () {
    day('2026-11-10');
    $project = ownedProject('in_preparazione');
    $project->requirements()->create(['label' => 'Logo', 'is_mandatory' => true, 'sort' => 1]);
    app(ProjectStatusService::class)->change($project, 'in_sviluppo', null);

    expect(runReminders('2026-11-17'))->toBe(1);

    app(ProjectStatusService::class)->change($project->fresh(), 'in_preparazione', null);

    expect(runReminders('2026-11-18'))->toBe(0);
});

it('warns about material waiting more than 7 days', function () {
    $project = ownedProject('in_preparazione');
    $foto = Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Foto, 'requested_at' => '2026-11-10 08:00']);
    Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Logo, 'requested_at' => '2026-11-10 08:00']);
    Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Testi, 'requested_at' => '2026-11-16 08:00']);

    expect(runReminders('2026-11-16'))->toBe(0)
        ->and(runReminders('2026-11-17'))->toBe(1)
        ->and($project->owner->notifications->first()->data['body'])
        ->toBe('Materiale atteso da più di 7 giorni: foto, logo — Hotel Rossi');

    $project->materials->each->update(['status' => 'ricevuto']);

    expect(runReminders('2026-11-18'))->toBe(0);
});

it('does not send twice on the same day', function () {
    ownedProject('pronto_da_sviluppare');

    expect(runReminders('2026-11-17'))->toBe(1)
        ->and(runReminders('2026-11-17'))->toBe(0)
        ->and(DB::table('notifications')->count())->toBe(1);
});

it('skips projects without owner', function () {
    ownedProject('pronto_da_sviluppare', ['owner_id' => null]);

    expect(runReminders('2026-11-17'))->toBe(0);
});

it('skips projects in a final state', function () {
    $project = ownedProject('pubblicato', ['due_date' => '2026-11-01']);
    Material::create(['project_id' => $project->id, 'kind' => MaterialKind::Foto, 'requested_at' => '2026-10-01 08:00']);

    expect(runReminders('2026-11-17'))->toBe(0);
});

it('schedules the command at 09:00', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'beddy:reminders'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 9 * * *')
        ->and($event->timezone)->toBe('Europe/Rome');
});

it('runs from the command and prints the count', function () {
    ownedProject('pronto_da_sviluppare');

    $this->artisan('beddy:reminders')->expectsOutput('Promemoria inviati: 1')->assertSuccessful();
});

function ownedActivity(string $due, array $attrs = []): InternalActivity
{
    return InternalActivity::factory()->create(array_merge([
        'name' => 'Aggiornamento server',
        'owner_id' => User::factory()->create()->id,
        'due_date' => $due,
    ], $attrs));
}

it('reminds the owner of an internal activity from 2 days before, every day, once a day', function () {
    $activity = ownedActivity('2026-12-10');

    expect(runReminders('2026-12-07'))->toBe(0)
        ->and(runReminders('2026-12-08'))->toBe(1)
        ->and(runReminders('2026-12-08'))->toBe(0)
        ->and(runReminders('2026-12-10'))->toBe(1)
        ->and(runReminders('2026-12-15'))->toBe(1);

    $last = $activity->owner->notifications()->latest('created_at')->first();
    expect($last->type)->toBe(ActivityDeadlineReminder::class)
        ->and($last->data['internal_activity_id'])->toBe($activity->id)
        ->and($last->data['body'])->toContain('scaduta da 5 giorni');
});

it('words the activity reminder by days left', function () {
    $activity = ownedActivity('2026-12-10');
    $message = fn (string $date) => (new ActivityDeadlineReminder($activity, CarbonImmutable::parse($date)))->message();

    expect($message('2026-12-08'))->toContain('scade tra 2 giorni')
        ->and($message('2026-12-09'))->toContain('scade domani')
        ->and($message('2026-12-10'))->toContain('scade oggi')
        ->and($message('2026-12-11'))->toContain('scaduta da 1 giorno');
});

it('skips completed activities, activities without owner or without due date', function () {
    ownedActivity('2026-12-10', ['completed_at' => now()]);
    ownedActivity('2026-12-10', ['owner_id' => null]);
    ownedActivity('2026-12-10', ['due_date' => null]);

    expect(runReminders('2026-12-12'))->toBe(0);
});
