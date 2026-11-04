<?php

namespace App\Domain\Users;

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InviteUser
{
    public function handle(string $name, string $email): User
    {
        Validator::make(
            ['name' => $name, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            ],
        )->validate();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Str::random(64),
            'is_active' => true,
            'invited_at' => now(),
        ]);

        $token = Password::broker()->createToken($user);

        $user->notify(new UserInvitation($token));

        return $user;
    }
}
