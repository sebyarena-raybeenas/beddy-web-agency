<?php

namespace App\Observers;

use App\Domain\Projects\RequirementSync;
use App\Models\ProjectRequirement;

class ProjectRequirementObserver
{
    private static bool $suspended = false;

    /**
     * Runs the callback without per-row evaluation (bulk copy of templates);
     * the caller evaluates once at the end.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutSync(callable $callback): mixed
    {
        $previous = self::$suspended;
        self::$suspended = true;

        try {
            return $callback();
        } finally {
            self::$suspended = $previous;
        }
    }

    public function created(ProjectRequirement $requirement): void
    {
        $this->evaluate($requirement);
    }

    public function deleted(ProjectRequirement $requirement): void
    {
        $this->evaluate($requirement);
    }

    public function updated(ProjectRequirement $requirement): void
    {
        if ($requirement->wasChanged(['is_done', 'is_mandatory'])) {
            $this->evaluate($requirement);
        }
    }

    private function evaluate(ProjectRequirement $requirement): void
    {
        if (self::$suspended) {
            return;
        }

        $project = $requirement->project()->first();

        if ($project) {
            app(RequirementSync::class)->evaluate($project);
        }
    }
}
