<?php

use App\Domain\Workflow\ProjectStatusService;
use App\Models\Project;
use App\Models\ProjectRequirement;
use App\Models\ProjectStatusChange;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

it('logs a project update with the authenticated user as causer', function () {
    $project = Project::factory()->create();
    $newOwner = User::factory()->create();
    $user = User::factory()->create();
    $this->actingAs($user);

    $project->update(['owner_id' => $newOwner->id]);

    $activity = Activity::query()->where('event', 'updated')->latest('id')->first();
    expect($activity)->not->toBeNull()
        ->and($activity->causer->is($user))->toBeTrue()
        ->and($activity->subject->is($project))->toBeTrue()
        ->and($activity->description)->toBe('modificato')
        ->and($activity->attribute_changes['attributes'])->toBe(['owner_id' => $newOwner->id]);
});

it('uses Italian descriptions for created and deleted', function () {
    $project = Project::factory()->create();
    expect(Activity::query()->where('event', 'created')->first()->description)->toBe('creato');

    $project->delete();
    expect(Activity::query()->where('event', 'deleted')->first()->description)->toBe('eliminato');
});

it('does not log status changes of a project as activity', function () {
    $project = Project::factory()->create();
    $before = Activity::count();

    app(ProjectStatusService::class)->change($project, 'in_sviluppo', User::factory()->create());

    expect(Activity::count())->toBe($before);
});

it('builds a timeline with status changes and ticked requirements, newest first', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $project = Project::factory()->create();
    $requirement = ProjectRequirement::create([
        'project_id' => $project->id, 'label' => 'Logo', 'is_mandatory' => true, 'sort' => 1,
    ]);

    $this->travel(1)->hours();
    $requirement->update(['is_done' => true, 'done_at' => now(), 'done_by' => $user->id]);
    $this->travel(1)->hours();
    app(ProjectStatusService::class)->change($project, 'in_sviluppo', $user);

    $timeline = $project->timeline();

    expect($timeline->first())->toBeInstanceOf(ProjectStatusChange::class)
        ->and($timeline->first()->to_status)->toBe('in_sviluppo');

    $tickIndex = $timeline->search(fn ($i) => $i instanceof Activity
        && $i->event === 'updated' && $i->subject_type === $requirement->getMorphClass());
    $statusIndex = $timeline->search(fn ($i) => $i instanceof ProjectStatusChange && $i->to_status === 'in_sviluppo');

    expect($tickIndex)->not->toBeFalse()->and($statusIndex)->toBeLessThan($tickIndex);

    $dates = $timeline->map(fn ($i) => $i instanceof Activity ? $i->created_at : $i->changed_at);
    expect($dates->values()->all())->toBe($dates->sortByDesc(fn ($d) => $d->getTimestamp())->values()->all());
});
