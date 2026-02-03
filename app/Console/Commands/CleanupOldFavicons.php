<?php

namespace App\Console\Commands;

use App\Settings\GeneralSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOldFavicons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'favicon:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bereinigt alte Favicon- und Logo-Dateien aus dem Storage';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $settings = new GeneralSetting;
        $currentFavicon = $settings->favicon;
        $currentLogo = $settings->logo;

        $this->info('Aktuelles Favicon: '.$currentFavicon);
        $this->info('Aktuelles Logo: '.$currentLogo);

        $files = Storage::disk('public')->files('img');
        $deletedCount = 0;

        foreach ($files as $file) {
            $filename = basename($file);

            // Überspringe das aktuelle Favicon und Logo
            if ($filename === $currentFavicon || $filename === $currentLogo) {
                continue;
            }

            // Überspringe Standard-Dateien
            if (in_array($filename, ['app_logo.png', 'avatar.png', 'logo.png', 'error503.jpg'])) {
                continue;
            }

            // Lösche alte Favicon- und Logo-Dateien
            if (preg_match('/^\d{14}_(favicon|logo)\.(png|jpg|jpeg|gif|svg|ico)$/', $filename)) {
                $this->info('Lösche alte Datei: '.$filename);
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        $this->info("Bereinigung abgeschlossen. {$deletedCount} Datei(en) gelöscht.");

        return 0;
    }
}
