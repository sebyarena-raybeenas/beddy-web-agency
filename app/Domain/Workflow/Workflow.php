<?php

namespace App\Domain\Workflow;

use InvalidArgumentException;

/**
 * Immutable view over one workflow definition from config/workflows.php.
 */
final readonly class Workflow
{
    /**
     * @param  array<string, string>  $states  state key => Italian label, in order
     * @param  array<string, array{0: int, 1: string}>  $deadlines  state key => [days, message]
     */
    public function __construct(
        public string $key,
        private array $states,
        private string $initial,
        private string $ready,
        private string $final,
        private string $readyMessage,
        private array $deadlines,
        private ?string $checklistTrigger,
    ) {}

    /**
     * Builds a workflow from its config array.
     *
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            states: $config['states'],
            initial: $config['initial'],
            ready: $config['ready'],
            final: $config['final'],
            readyMessage: $config['ready_message'],
            deadlines: $config['deadlines'],
            checklistTrigger: $config['checklist_trigger'],
        );
    }

    /**
     * @return array<string, string>
     */
    public function states(): array
    {
        return $this->states;
    }

    public function initial(): string
    {
        return $this->initial;
    }

    public function ready(): string
    {
        return $this->ready;
    }

    public function has(string $state): bool
    {
        return array_key_exists($state, $this->states);
    }

    /**
     * @throws InvalidArgumentException when the state is not part of this workflow
     */
    public function label(string $state): string
    {
        if (! $this->has($state)) {
            throw new InvalidArgumentException("Stato [{$state}] non valido per il flusso [{$this->key}].");
        }

        return $this->states[$state];
    }

    /**
     * Days allowed in the given state, or null when it has no deadline.
     */
    public function deadlineDays(string $state): ?int
    {
        return isset($this->deadlines[$state]) ? $this->deadlines[$state][0] : null;
    }

    /**
     * Reminder message for the given state, or null when it has no deadline.
     */
    public function deadlineMessage(string $state): ?string
    {
        return isset($this->deadlines[$state]) ? $this->deadlines[$state][1] : null;
    }

    /**
     * Raw template with the ':project' placeholder left for the caller to replace.
     */
    public function readyMessage(): string
    {
        return $this->readyMessage;
    }

    public function checklistTrigger(): ?string
    {
        return $this->checklistTrigger;
    }

    public function isFinal(string $state): bool
    {
        return $state === $this->final;
    }
}
