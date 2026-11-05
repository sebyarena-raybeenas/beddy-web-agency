<?php

namespace Database\Seeders;

use App\Domain\Clients\DomainService;
use App\Domain\Projects\ProjectCreator;
use App\Domain\Workflow\ProjectStatusService;
use App\Enums\DomainOwnership;
use App\Enums\HostingStatus;
use App\Enums\MaterialStatus;
use App\Models\Client;
use App\Models\ClientContact;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fictional demo data for local development. Runs unconditionally; the
 * environment guard lives in DatabaseSeeder.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Client::where('name', 'Hotel Rossi')->exists()) {
            return;
        }

        $demo = $this->user('Utente Demo', 'demo@example.test');
        $collega = $this->user('Collega Demo', 'collega@example.test');

        $clients = [];
        foreach ([
            ['Hotel Rossi', '01234567890', 'Via dei Fiori 1, Roma', true, 'Assistenza annuale', HostingStatus::Nostro],
            ['Ristorante Da Mario', '02345678901', 'Piazza Centrale 5, Milano', false, null, HostingStatus::Suo],
            ['Studio Bianchi', '03456789012', 'Corso Italia 20, Torino', true, 'Assistenza base', HostingStatus::Nostro],
            ['Panificio Verdi', '04567890123', 'Via del Forno 3, Bologna', false, null, HostingStatus::DaAttivare],
            ['Autofficina Neri', '05678901234', 'Via Motori 8, Napoli', false, null, HostingStatus::Suo],
            ['Gelateria Blu', '06789012345', 'Lungomare 12, Bari', true, 'Assistenza base', HostingStatus::Nostro],
        ] as [$name, $vat, $address, $assist, $assistType, $hosting]) {
            $clients[$name] = Client::create([
                'name' => $name,
                'vat_number' => $vat,
                'address' => $address,
                'has_assistance' => $assist,
                'assistance_type' => $assistType,
                'hosting_status' => $hosting,
                'origin' => 'manuale',
                'created_by' => $demo->id,
            ]);

            ClientContact::create([
                'client_id' => $clients[$name]->id,
                'name' => 'Referente '.$name,
                'role' => 'Titolare',
                'phones' => ['+39 000 0000000', '+39 000 1111111'],
                'emails' => ['info@'.str($name)->slug().'.test', 'amministrazione@'.str($name)->slug().'.test'],
                'is_primary' => true,
            ]);
        }

        // 1. Hotel Rossi: nuovo, in preparazione, 4/6 requirements, foto atteso since 9 days ago.
        $rossi = $this->project($clients['Hotel Rossi'], 'Sito Hotel Rossi', 'nuovo', $demo, $demo);
        $this->tick($rossi, ['Informazioni', 'Cliente contattato', 'Struttura']);
        $this->receive($rossi, 'logo');
        $rossi->materials()->where('kind', 'foto')->update(['requested_at' => now()->subDays(9)]);

        // 2. Ready to develop: every requirement done.
        $mario = $this->project($clients['Ristorante Da Mario'], 'Sito Ristorante Da Mario', 'nuovo', $demo, $demo);
        $this->completeAll($mario);

        // 3. In development, past deadline, with principale + secondario domains.
        $bianchi = $this->project($clients['Studio Bianchi'], 'Sito Studio Bianchi', 'nuovo', $collega, $demo);
        $this->completeAll($bianchi);
        $this->advance($bianchi, 'in_sviluppo', $collega);
        $bianchi->forceFill(['due_date' => today()->subDays(4)])->save();
        $this->domains($bianchi, 'studiobianchi-demo.test', 'studiobianchi-demo.it');

        // 4. Published site.
        $verdi = $this->project($clients['Panificio Verdi'], 'Sito Panificio Verdi', 'nuovo', $demo, $demo);
        $this->completeAll($verdi);
        $this->advance($verdi, 'pubblicato', $demo);
        $this->domains($verdi, 'panificioverdi-demo.test');

        // 5. Redesign waiting for client changes, no owner.
        $neri = $this->project($clients['Autofficina Neri'], 'Rifacimento Autofficina Neri', 'rifacimento', null, $demo);
        $this->completeAll($neri);
        $this->advance($neri, 'feedback_modifiche', $demo);

        // 6. Site being published: generates the go-live checklist.
        $blu = $this->project($clients['Gelateria Blu'], 'Sito Gelateria Blu', 'nuovo', $collega, $demo);
        $this->completeAll($blu);
        $this->advance($blu, 'in_pubblicazione', $collega);
        $this->domains($blu, 'gelateriablu-demo.test');

        // 7. Modifica in preparazione, 1 of 3 requirements done.
        $modBianchi = $this->project($clients['Studio Bianchi'], 'Modifica orari Studio Bianchi', 'modifica', $demo, $demo);
        $this->tick($modBianchi, ['Richiesta']);

        // 8. Modifica in progress.
        $modMario = $this->project($clients['Ristorante Da Mario'], 'Modifica menu Ristorante Da Mario', 'modifica', $collega, $demo);
        $this->completeAll($modMario);
        $this->advance($modMario, 'in_modifica', $collega);
    }

    private function user(string $name, string $email): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );
    }

    private function project(Client $client, string $name, string $type, ?User $owner, User $by): Project
    {
        return app(ProjectCreator::class)->create([
            'client_id' => $client->id,
            'name' => $name,
            'type' => $type,
            'owner_id' => $owner?->id,
        ], $by);
    }

    /** Marks requirements done by label prefix. */
    private function tick(Project $project, array $prefixes): void
    {
        foreach ($project->requirements()->get() as $requirement) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($requirement->label, $prefix)) {
                    $requirement->update(['is_done' => true, 'done_at' => now()]);
                }
            }
        }
    }

    private function receive(Project $project, string $kind): void
    {
        $project->materials()->where('kind', $kind)->firstOrFail()->update(['status' => MaterialStatus::Ricevuto]);
    }

    /** Resolves every requirement; the project reaches the ready state automatically. */
    private function completeAll(Project $project): void
    {
        foreach ($project->materials()->get() as $material) {
            $material->update(['status' => MaterialStatus::Ricevuto]);
        }

        foreach ($project->requirements()->where('is_done', false)->get() as $requirement) {
            $requirement->update(['is_done' => true, 'done_at' => now()]);
        }
    }

    private function advance(Project $project, string $status, User $by): void
    {
        app(ProjectStatusService::class)->change($project->refresh(), $status, $by);
    }

    private function domains(Project $project, string $primary, ?string $secondary = null): void
    {
        $main = Domain::create([
            'client_id' => $project->client_id,
            'name' => $primary,
            'role' => 'secondario',
            'ownership' => DomainOwnership::Nostro,
        ]);
        app(DomainService::class)->setPrimary($project, $main);

        if ($secondary) {
            Domain::create([
                'client_id' => $project->client_id,
                'project_id' => $project->id,
                'name' => $secondary,
                'role' => 'secondario',
                'ownership' => DomainOwnership::Suo,
            ]);
        }
    }
}
