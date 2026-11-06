<?php

namespace App\Console\Commands;

use App\Domain\Users\InviteUser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

#[Signature('beddy:invite {name : Nome e cognome} {email : Indirizzo email}')]
#[Description('Crea un utente e gli invia l\'email di invito per impostare la password')]
class InviteUserCommand extends Command
{
    public function handle(InviteUser $invite): int
    {
        try {
            $user = $invite->handle($this->argument('name'), $this->argument('email'));
        } catch (ValidationException $e) {
            $this->error('Invito non inviato:');

            foreach ($e->validator->errors()->all() as $message) {
                $this->line(" - {$message}");
            }

            return self::FAILURE;
        }

        $this->info("Invito inviato a {$user->email}.");

        return self::SUCCESS;
    }
}
