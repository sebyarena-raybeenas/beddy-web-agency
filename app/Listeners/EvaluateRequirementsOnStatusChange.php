<?php

namespace App\Listeners;

use App\Domain\Projects\RequirementSync;
use App\Events\ProjectStatusChanged;

/**
 * A manual move back to the initial state with every mandatory requirement
 * already done must land in "ready" again (the ready state is automatic).
 * RequirementSync depends on ProjectStatusService, so a listener avoids a
 * circular dependency; evaluate() is idempotent for any other transition.
 */
class EvaluateRequirementsOnStatusChange
{
    public function __construct(private RequirementSync $sync) {}

    public function handle(ProjectStatusChanged $event): void
    {
        $workflow = $event->project->workflow();

        if ($event->to === $workflow->initial()) {
            $this->sync->evaluate($event->project);
        }
    }
}
