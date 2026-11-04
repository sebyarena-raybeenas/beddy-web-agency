<?php

use App\Domain\Reminders\ReminderService;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\MaterialKind;
use App\Models\Material;
use App\Models\Project;
use App\Models\User;
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
