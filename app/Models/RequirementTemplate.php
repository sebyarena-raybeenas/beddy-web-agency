<?php

namespace App\Models;

use App\Enums\MaterialKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['flow', 'label', 'is_mandatory', 'material_kind', 'sort'])]
class RequirementTemplate extends Model
{
    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
            'material_kind' => MaterialKind::class,
        ];
    }
}
