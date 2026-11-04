<?php

namespace App\Observers;

use App\Domain\Projects\RequirementSync;
use App\Models\ProjectRequirement;

class ProjectRequirementObserver
{
    public function saved(ProjectRequirement $requirement): void
    {
        if ($requirement->wasRecentlyCreated || ! $requirement->wasChanged('is_done')) {
            return;
        }

        app(RequirementSync::class)->evaluate($requirement->project()->first());
    }
}
