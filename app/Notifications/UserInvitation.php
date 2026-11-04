<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification
{
    public function __construct(public string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = Filament::getPanel('admin')->getResetPasswordUrl($this->token, $notifiable);

        return (new MailMessage)
            ->subject('Sei stato invitato su Beddy Web Agency')
            ->greeting("Ciao {$notifiable->name},")
            ->line('Sei stato invitato a unirti a Beddy Web Agency. Imposta la tua password per accedere.')
            ->action('Imposta la password', $url)
            ->line('Se non te lo aspettavi, puoi ignorare questa email.');
    }
}
