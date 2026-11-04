<?php

namespace App\Models\Concerns;

use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait LogsModelActivity
{
    use LogsActivity;

    /**
     * Attributes that must never be written to the activity log.
     *
     * @return array<int, string>
     */
    protected function activityLogExcept(): array
    {
        return [];
    }

    public function getActivitylogOptions(): LogOptions
    {
        $options = LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->setDescriptionForEvent(fn (string $event) => match ($event) {
                'created' => 'creato',
                'updated' => 'modificato',
                'deleted' => 'eliminato',
                default => $event,
            });

        if ($except = $this->activityLogExcept()) {
            $options->logExcept($except)->dontLogIfAttributesChangedOnly($except);
        }

        return $options;
    }
}
