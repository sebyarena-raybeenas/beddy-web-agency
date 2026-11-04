<?php

namespace App\Notifications;

class ProjectReadyReminder extends ProjectReminder
{
    public function message(): string
    {
        return str_replace(':project', $this->project->name, $this->project->workflow()->readyMessage());
    }
}
