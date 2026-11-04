<?php

namespace App\Console\Commands;

use App\Domain\Reminders\ReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('beddy:reminders')]
#[Description('Invia i promemoria giornalieri ai responsabili dei progetti')]
class SendDailyReminders extends Command
{
    public function handle(ReminderService $service): int
    {
        $count = $service->run(CarbonImmutable::today());

        $this->info("Promemoria inviati: {$count}");

        return self::SUCCESS;
    }
}
