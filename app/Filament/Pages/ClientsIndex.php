<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Models\Domain;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Url;

class ClientsIndex extends Page
{
    protected static ?string $slug = 'clienti';

    protected static ?string $title = 'Clienti';

    protected ?string $heading = '';

    protected static ?string $navigationLabel = 'Clienti';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected string $view = 'filament.pages.clients-index';

    #[Url]
    public string $search = '';

    public function createUrl(): string
    {
        return ClientResource::getUrl('create');
    }

    /**
     * @return Collection<int, Client>
     */
    public function clients(): Collection
    {
        $term = trim($this->search);

        return Client::query()
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%'.$term.'%';
                $domain = '%'.Domain::normalizeName($term).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('name', 'like', $like)
                    ->orWhereHas('domains', fn (Builder $d) => $d->where('name', 'like', $domain))
                    ->orWhereHas('projects.domains', fn (Builder $d) => $d->where('name', 'like', $domain)));
            })
            ->withCount(['projects as active_projects_count' => fn (Builder $p) => $p->notFinal()])
            ->with([
                'contacts' => fn ($c) => $c->where('is_primary', true),
                'projects:id,client_id,type,status',
                'projects.domains' => fn ($d) => $d->where('role', 'principale'),
            ])
            ->orderBy('name')
            ->get();
    }

    protected function getViewData(): array
    {
        return ['clients' => $this->clients()];
    }
}
