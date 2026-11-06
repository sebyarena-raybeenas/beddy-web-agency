<?php

namespace App\Notifications;

class DeadlineReminder extends ProjectReminder
{
    public function message(): string
    {
        return $this->project->workflow()->deadlineMessage($this->project->status).' — '.$this->project->name;
    }
}
