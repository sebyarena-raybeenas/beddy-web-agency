<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Users\InviteUser;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(InviteUser::class)->handle($data['name'], $data['email']);
    }
}
