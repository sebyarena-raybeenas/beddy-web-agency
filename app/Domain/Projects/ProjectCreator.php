<?php

namespace App\Domain\Projects;

use App\Enums\MaterialStatus;
use App\Enums\ProjectType;
use App\Models\Material;
use App\Models\Project;
use App\Models\RequirementTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectCreator
{
    public function create(array $attributes, ?User $by): Project
    {
        return DB::transaction(function () use ($attributes, $by) {
            $type = $attributes['type'];
            $type = $type instanceof ProjectType ? $type : ProjectType::from($type);

            $project = Project::create($attributes + ['created_by' => $by?->id]);

            $templates = RequirementTemplate::where('flow', $type->flow())->orderBy('sort')->get();

            foreach ($templates as $template) {
                $requirement = $project->requirements()->create([
                    'template_id' => $template->id,
                    'label' => $template->label,
                    'is_mandatory' => $template->is_mandatory,
                    'sort' => $template->sort,
                ]);

                if ($template->material_kind) {
                    Material::create([
                        'project_id' => $project->id,
                        'requirement_id' => $requirement->id,
                        'kind' => $template->material_kind,
                        'status' => MaterialStatus::Atteso,
                        'requested_at' => now(),
                    ]);
                }
            }

            return $project;
        });
    }
}
