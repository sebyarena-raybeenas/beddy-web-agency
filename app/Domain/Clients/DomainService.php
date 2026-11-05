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

    /**
     * The client's other principale domain, if any (the one a new principale would replace).
     */
    public function existingClientPrimary(int $clientId, ?int $exceptId = null): ?Domain
    {
        return Domain::query()
            ->where('client_id', $clientId)
            ->where('role', DomainRole::Principale)
            ->when($exceptId !== null, fn ($q) => $q->whereKeyNot($exceptId))
            ->orderBy('name')
            ->first();
    }

    /**
     * Makes the domain the client's principale: every other domain of the client becomes secondario.
     * A project whose primary domain was demoted loses it; the domain's own project (if any) points to it.
     */
    public function makeClientPrimary(Domain $domain): void
    {
        DB::transaction(function () use ($domain) {
            $others = Domain::query()
                ->where('client_id', $domain->client_id)
                ->whereKeyNot($domain->id);

            Project::query()
                ->where('client_id', $domain->client_id)
                ->whereIn('primary_domain_id', (clone $others)->select('id'))
                ->update(['primary_domain_id' => null]);

            $others->update(['role' => DomainRole::Secondario]);

            $domain->forceFill(['role' => DomainRole::Principale])->save();

            if ($domain->project_id !== null) {
                Project::query()->whereKey($domain->project_id)->update(['primary_domain_id' => $domain->id]);
            }
        });
    }

    /**
     * Turns the domain into a secondario; a project that used it as primary loses it.
     */
    public function makeSecondary(Domain $domain): void
    {
        DB::transaction(function () use ($domain) {
            $domain->forceFill(['role' => DomainRole::Secondario])->save();

            Project::query()->where('primary_domain_id', $domain->id)->update(['primary_domain_id' => null]);
        });
    }
}
