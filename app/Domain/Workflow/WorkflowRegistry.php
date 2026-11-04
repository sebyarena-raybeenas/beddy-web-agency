<?php

namespace App\Domain\Workflow;

use App\Enums\ProjectType;
use InvalidArgumentException;

/**
 * Resolves workflow definitions from config/workflows.php.
 *
 * Registered as a singleton in AppServiceProvider; methods are instance methods
 * so the config-backed definitions can be memoised per instance.
 */
class WorkflowRegistry
{
    /** @var array<string, Workflow> */
    private array $workflows = [];

    public function for(ProjectType $type): Workflow
    {
        return $this->get($type->flow());
    }

    /**
     * @throws InvalidArgumentException when no workflow is defined for the flow
     */
    public function get(string $flow): Workflow
    {
        if (isset($this->workflows[$flow])) {
            return $this->workflows[$flow];
        }

        $config = config("workflows.{$flow}");

        if (! is_array($config)) {
            throw new InvalidArgumentException("Flusso di lavoro [{$flow}] non definito.");
        }

        return $this->workflows[$flow] = Workflow::fromConfig($flow, $config);
    }
}
