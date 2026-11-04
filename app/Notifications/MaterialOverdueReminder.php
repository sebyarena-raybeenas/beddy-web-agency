<?php

namespace App\Notifications;

use App\Models\Project;

class MaterialOverdueReminder extends ProjectReminder
{
    /**
     * @param  array<int, string>  $kinds  lowercase Italian labels
     */
    public function __construct(Project $project, public array $kinds)
    {
        parent::__construct($project);
    }

    public function message(): string
    {
        return 'Materiale atteso da più di 7 giorni: '.implode(', ', $this->kinds).' — '.$this->project->name;
    }
}
