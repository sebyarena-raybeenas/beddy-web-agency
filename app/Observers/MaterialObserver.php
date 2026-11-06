<?php

namespace App\Observers;

use App\Enums\MaterialStatus;
use App\Models\Material;

class MaterialObserver
{
    public function saved(Material $material): void
    {
        if (! $material->wasChanged('status') || ! $material->requirement) {
            return;
        }

        $done = $material->status->isResolved();

        if ($material->status === MaterialStatus::Ricevuto) {
            $material->forceFill(['received_at' => now()])->saveQuietly();
        }

        // Saved through Eloquent so ProjectRequirementObserver evaluates the project.
        $material->requirement->update([
            'is_done' => $done,
            'done_at' => $done ? now() : null,
            'done_by' => $done ? auth()->id() : null,
        ]);
    }
}
