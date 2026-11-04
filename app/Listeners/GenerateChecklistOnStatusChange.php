<?php

namespace App\Listeners;

use App\Domain\Checklists\ChecklistGenerator;
use App\Events\ProjectStatusChanged;

class GenerateChecklistOnStatusChange
{
    public function __construct(private ChecklistGenerator $generator) {}

    public function handle(ProjectStatusChanged $event): void
    {
        $this->generator->generate($event->project, $event->to);
    }
}
