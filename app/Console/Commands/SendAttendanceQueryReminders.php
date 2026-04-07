<?php

namespace App\Console\Commands;

use App\Jobs\SendAttendanceQueryReminderJob;
use Illuminate\Console\Command;

class SendAttendanceQueryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sendet Erinnerungen für Anwesenheitsabfragen, die in 3 Tagen ablaufen';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sende Anwesenheitsabfragen-Erinnerungen...');

        $job = new SendAttendanceQueryReminderJob();
        $job->handle();

        $this->info('Erinnerungen wurden versendet.');

        return Command::SUCCESS;
    }
}

