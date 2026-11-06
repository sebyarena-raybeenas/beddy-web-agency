<?php

namespace App\Domain\Projects;

use App\Domain\Workflow\ProjectStatusService;
use App\Models\Project;

class RequirementSync
{
    public function __construct(private ProjectStatusService $status) {}

    public function evaluate(Project $project): void
    {
        $workflow = $project->workflow();

        // Checks the per-project copy of is_mandatory, never the template.
        $allDone = ! $project->requirements()
            ->where('is_mandatory', true)
            ->where('is_done', false)
            ->exists();

        if ($project->status === $workflow->initial() && $allDone) {
            $this->status->applySystem($project, $workflow->ready());
        } elseif ($project->status === $workflow->ready() && ! $allDone) {
            $this->status->applySystem($project, $workflow->initial());
        }
    }
}
