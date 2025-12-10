<?php

namespace App\Console\Commands;

use App\Settings\GeneralSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ValidateFaviconCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'favicon:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validiert, ob Favicon und Logo existieren und setzt sie bei Bedarf auf Standard zurück';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $settings = new GeneralSetting();
        $changed = false;

        // Prüfe Favicon
        if ($settings->favicon && $settings->favicon !== 'app_logo.png') {
            if (!Storage::disk('public')->exists('img/' . $settings->favicon)) {
                $this->warn('Favicon nicht gefunden: ' . $settings->favicon);
                $this->info('Setze Favicon auf Standard-Logo zurück: app_logo.png');

                Log::warning('Favicon nicht gefunden: ' . $settings->favicon . '. Setze auf Standard-Logo zurück.');

                $settings->favicon = 'app_logo.png';
                $changed = true;
            } else {
                $this->info('Favicon gefunden: ' . $settings->favicon);
            }
        } else {
            $this->info('Favicon ist bereits Standard-Logo: app_logo.png');
        }

        // Prüfe Logo
        if ($settings->logo && $settings->logo !== 'app_logo.png') {
            if (!Storage::disk('public')->exists('img/' . $settings->logo)) {
                $this->warn('Logo nicht gefunden: ' . $settings->logo);
                $this->info('Setze Logo auf Standard-Logo zurück: app_logo.png');

                Log::warning('Logo nicht gefunden: ' . $settings->logo . '. Setze auf Standard-Logo zurück.');

                $settings->logo = 'app_logo.png';
                $changed = true;
            } else {
                $this->info('Logo gefunden: ' . $settings->logo);
            }
        } else {
            $this->info('Logo ist bereits Standard-Logo: app_logo.png');
        }

        if ($changed) {
            $settings->save();
            $this->info('Einstellungen wurden aktualisiert.');
        } else {
            $this->info('Keine Änderungen erforderlich.');
        }

        return 0;
    }
}

