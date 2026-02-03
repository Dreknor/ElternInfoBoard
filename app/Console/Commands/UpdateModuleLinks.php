<?php

namespace App\Console\Commands;

use App\Model\Module;
use Illuminate\Console\Command;

class UpdateModuleLinks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:update-links';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Nachrichten and Termine module links for dashboard';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Update Nachrichten Module
        $nachrichtenModule = Module::query()->where('setting', 'Nachrichten')->first();

        if ($nachrichtenModule) {
            $options = $nachrichtenModule->options;

            // Remove home-view
            if (isset($options['home-view'])) {
                unset($options['home-view']);
                $this->info('Removed home-view from Nachrichten module');
            }

            // Update nav link
            if (isset($options['nav'])) {
                $options['nav']['link'] = 'nachrichten';
                $options['nav']['bottom-nav'] = 'true';
                $this->info('Updated Nachrichten nav link to /nachrichten');
            }

            $nachrichtenModule->options = $options;
            $nachrichtenModule->save();

            $this->info('✓ Nachrichten module updated successfully');
        } else {
            $this->warn('Nachrichten module not found');
        }

        // Update Termine Module
        $termineModule = Module::query()->where('setting', 'Termine')->first();

        if ($termineModule) {
            $options = $termineModule->options;

            // Remove home-view-top
            if (isset($options['home-view-top'])) {
                unset($options['home-view-top']);
                $this->info('Removed home-view-top from Termine module');
            }

            // Add or update navigation
            if (! isset($options['nav'])) {
                $options['nav'] = [
                    'name' => 'Termine',
                    'link' => 'termin',
                    'icon' => 'far fa-calendar-alt',
                    'bottom-nav' => 'true',
                ];
                $this->info('Added nav to Termine module');
            } else {
                $options['nav']['link'] = 'termin';
                $options['nav']['bottom-nav'] = 'true';
                $this->info('Updated Termine nav link to /termin');
            }

            $termineModule->options = $options;
            $termineModule->save();

            $this->info('✓ Termine module updated successfully');
        } else {
            $this->warn('Termine module not found');
        }

        $this->info('');
        $this->info('Module configuration updated. Please clear cache:');
        $this->info('php artisan cache:clear');

        return 0;
    }
}
