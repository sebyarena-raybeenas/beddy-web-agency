<?php

use App\Domain\Workflow\Workflow;
use App\Domain\Workflow\WorkflowRegistry;
use App\Enums\ProjectType;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->registry = app(WorkflowRegistry::class);
});

it('maps nuovo and rifacimento to the sito flow', function () {
    expect(ProjectType::Nuovo->flow())->toBe('sito')
        ->and(ProjectType::Rifacimento->flow())->toBe('sito')
        ->and(ProjectType::Modifica->flow())->toBe('modifica');
});

it('has the expected project type labels', function () {
    expect(ProjectType::Nuovo->label())->toBe('Nuovo sito')
        ->and(ProjectType::Modifica->label())->toBe('Modifica')
        ->and(ProjectType::Rifacimento->label())->toBe('Rifacimento');
});

it('resolves the workflow for a project type', function () {
    $workflow = $this->registry->for(ProjectType::Rifacimento);

    expect($workflow)->toBeInstanceOf(Workflow::class)
        ->and($workflow->key)->toBe('sito');
});

it('throws for an unknown flow', function () {
    expect(fn () => $this->registry->get('inesistente'))
        ->toThrow(InvalidArgumentException::class);
});

it('keeps the sito states in order with Italian labels', function () {
    $states = $this->registry->get('sito')->states();

    expect(array_keys($states))->toBe([
        'in_preparazione',
        'pronto_da_sviluppare',
        'in_sviluppo',
        'in_attesa_feedback',
        'feedback_modifiche',
        'in_pubblicazione',
        'pubblicato',
    ])->and($states['in_attesa_feedback'])
        ->toBe('Presentazione sito: in attesa di feedback');
});

it('modifica flow has no pronto_da_sviluppare state', function () {
    $workflow = $this->registry->get('modifica');

    expect($workflow->has('pronto_da_sviluppare'))->toBeFalse()
        ->and($workflow->has('pronto_da_modificare'))->toBeTrue()
        ->and(array_keys($workflow->states()))->toBe([
            'in_preparazione',
            'pronto_da_modificare',
            'in_modifica',
            'in_attesa_feedback',
            'feedback_altre_modifiche',
            'modifiche_pubblicate',
        ]);
});

it('exposes initial and ready states', function () {
    $sito = $this->registry->get('sito');
    $modifica = $this->registry->get('modifica');

    expect($sito->initial())->toBe('in_preparazione')
        ->and($sito->ready())->toBe('pronto_da_sviluppare')
        ->and($modifica->ready())->toBe('pronto_da_modificare');
});

it('returns the deadline days for a state', function () {
    expect($this->registry->get('sito')->deadlineDays('in_sviluppo'))->toBe(7)
        ->and($this->registry->get('sito')->deadlineMessage('in_sviluppo'))
        ->toBe("Hai finito? Contatta il cliente per l'appuntamento.");
});

it('returns null deadlines for states without a deadline', function () {
    $sito = $this->registry->get('sito');

    expect($sito->deadlineDays('in_preparazione'))->toBeNull()
        ->and($sito->deadlineMessage('in_preparazione'))->toBeNull()
        ->and($sito->deadlineDays('stato_inesistente'))->toBeNull()
        ->and($sito->deadlineMessage('stato_inesistente'))->toBeNull();
});

it('has no checklist trigger for modifica', function () {
    expect($this->registry->get('modifica')->checklistTrigger())->toBeNull()
        ->and($this->registry->get('sito')->checklistTrigger())->toBe('in_pubblicazione');
});

it('exposes the ready message with the project placeholder', function () {
    expect($this->registry->get('sito')->readyMessage())
        ->toBe('Il sito :project è pronto da sviluppare, puoi iniziare.')
        ->and($this->registry->get('modifica')->readyMessage())
        ->toBe('La modifica :project è pronta, puoi iniziare.');
});

it('identifies the final state of each flow', function () {
    $sito = $this->registry->get('sito');
    $modifica = $this->registry->get('modifica');

    expect($sito->isFinal('pubblicato'))->toBeTrue()
        ->and($sito->isFinal('in_sviluppo'))->toBeFalse()
        ->and($modifica->isFinal('modifiche_pubblicate'))->toBeTrue()
        ->and($modifica->isFinal('pubblicato'))->toBeFalse();
});

it('throws for an unknown state label', function () {
    expect(fn () => $this->registry->get('sito')->label('stato_inesistente'))
        ->toThrow(InvalidArgumentException::class);
});

it('returns the label of a known state', function () {
    expect($this->registry->get('sito')->label('in_sviluppo'))->toBe('In sviluppo');
});

it('registers the registry as a singleton', function () {
    expect(app(WorkflowRegistry::class))->toBe(app(WorkflowRegistry::class));
});
