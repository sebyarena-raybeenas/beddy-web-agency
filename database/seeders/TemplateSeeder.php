<?php

namespace Database\Seeders;

use App\Models\RequirementTemplate;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['sito', 'Informazioni preliminari complete (contatti, acconto, dominio, hosting, assistenza)', null, 1],
            ['sito', 'Cliente contattato', null, 2],
            ['sito', 'Struttura sito inviata', null, 3],
            ['sito', 'Foto ricevute', 'foto', 4],
            ['sito', 'Testi ricevuti', 'testi', 5],
            ['sito', 'Logo ricevuto', 'logo', 6],
            ['modifica', 'Richiesta del cliente chiara e registrata', null, 1],
            ['modifica', 'Cliente contattato', null, 2],
            ['modifica', 'Materiale ricevuto', 'altro', 3],
        ];

        foreach ($rows as [$flow, $label, $kind, $sort]) {
            RequirementTemplate::updateOrCreate(
                ['flow' => $flow, 'label' => $label],
                ['is_mandatory' => true, 'material_kind' => $kind, 'sort' => $sort],
            );
        }
    }
}
