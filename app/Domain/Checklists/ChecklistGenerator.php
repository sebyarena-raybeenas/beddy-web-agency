<?php

namespace App\Domain\Checklists;

use App\Models\ChecklistTemplate;
use App\Models\Project;
use App\Models\Task;

class ChecklistGenerator
{
    /**
     * Creates the tasks of every checklist triggered by $status for the
     * project's flow, skipping items that already have a task.
     *
     * @return int number of tasks created
     */
    public function generate(Project $project, string $status): int
    {
        $created = 0;

        $templates = ChecklistTemplate::where('flow', $project->workflow()->key)
            ->where('trigger_status', $status)
            ->with('items')
            ->get();

        foreach ($templates as $template) {
            $existing = Task::where('project_id', $project->id)
                ->whereIn('template_item_id', $template->items->modelKeys())
                ->pluck('template_item_id')
                ->all();

            foreach ($template->items as $item) {
                if (in_array($item->id, $existing, true)) {
                    continue;
                }

                Task::create([
                    'project_id' => $project->id,
                    'template_item_id' => $item->id,
                    'title' => $item->title,
                    'assignee_id' => $project->owner_id,
                    'sort' => $item->sort,
                ]);
                $created++;
            }
        }

        return $created;
    }
}
