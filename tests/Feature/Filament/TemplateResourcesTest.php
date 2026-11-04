<?php

use App\Filament\Resources\ChecklistTemplates\Pages\CreateChecklistTemplate;
use App\Filament\Resources\RequirementTemplates\Pages\CreateRequirementTemplate;
use App\Models\ChecklistTemplate;
use App\Models\RequirementTemplate;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('creates a requirement template', function () {
    Livewire::test(CreateRequirementTemplate::class)
        ->fillForm(['flow' => 'sito', 'label' => 'Brief firmato', 'is_mandatory' => true, 'sort' => 7])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(RequirementTemplate::where('label', 'Brief firmato')->exists())->toBeTrue();
});

it('creates a checklist template with items', function () {
    Livewire::test(CreateChecklistTemplate::class)
        ->fillForm([
            'name' => 'Collaudo',
            'flow' => 'sito',
            'trigger_status' => 'in_pubblicazione',
            'items' => [['title' => 'Test form'], ['title' => 'Test mobile']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $template = ChecklistTemplate::where('name', 'Collaudo')->firstOrFail();
    expect($template->items->pluck('title')->all())->toBe(['Test form', 'Test mobile']);
});
