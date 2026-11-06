<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Foundation\Events\Dispatchable;

class ProjectStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Project $project,
        public readonly ?string $from,
        public readonly string $to,
    ) {}
}
