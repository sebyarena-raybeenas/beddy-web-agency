<?php

namespace App\Domain\Clients;

use App\Enums\DomainRole;
use App\Models\Domain;
use App\Models\Project;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DomainService
{
    /**
     * @throws ValidationException when the domain is not usable for this project
     */
    public function setPrimary(Project $project, Domain $domain): void
    {
        if ($domain->client_id !== $project->client_id) {
            throw ValidationException::withMessages([
                'domain' => 'Il dominio non appartiene al cliente di questo progetto',
            ]);
        }

        if ($domain->project_id !== null && $domain->project_id !== $project->id) {
            throw ValidationException::withMessages([
                'domain' => 'Il dominio è già assegnato a un altro progetto',
            ]);
        }

        DB::transaction(function () use ($project, $domain) {
            Domain::query()
                ->where('project_id', $project->id)
                ->whereKeyNot($domain->id)
                ->update(['role' => DomainRole::Secondario]);

            $domain->forceFill([
                'project_id' => $project->id,
                'role' => DomainRole::Principale,
            ])->save();

            $project->forceFill(['primary_domain_id' => $domain->id])->save();
        });
    }
}
