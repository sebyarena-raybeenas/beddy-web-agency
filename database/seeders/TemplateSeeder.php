<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
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

        $this->seedGoLiveChecklist();
    }

    private function seedGoLiveChecklist(): void
    {
        $checklist = ChecklistTemplate::updateOrCreate(
            ['flow' => 'sito', 'name' => 'Messa online'],
            ['trigger_status' => 'in_pubblicazione'],
        );

        $titles = [
            'Responsive', 'Traduzioni', 'Robots', 'Sitemap', 'Search Console', 'Analytics',
            'Recaptcha', 'Wordfence', 'Cache', 'Disabilitare sitemap SEO Yoast', 'Cambio password',
            'Rimozione plugin', 'Codice WPML', 'Contact form', '404', 'Google Business',
            'Redirect vecchio sito',
        ];

        foreach ($titles as $index => $title) {
            $checklist->items()->updateOrCreate(['title' => $title], ['sort' => $index + 1]);
        }
    }
}
