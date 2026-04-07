<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Model\Module;

echo "Updating Nachrichten module...\n";

// Update Nachrichten Module
$nachrichtenModule = Module::query()->where('setting', 'Nachrichten')->first();

if ($nachrichtenModule) {
    $options = $nachrichtenModule->options;

    // Remove home-view
    if (isset($options['home-view'])) {
        unset($options['home-view']);
        echo "  - Removed home-view\n";
    }

    // Update nav link
    if (isset($options['nav'])) {
        $options['nav']['link'] = 'nachrichten';
        $options['nav']['bottom-nav'] = 'true';
        echo "  - Updated nav link to /nachrichten\n";
    }

    $nachrichtenModule->options = $options;
    $nachrichtenModule->save();

    echo "✓ Nachrichten module updated successfully\n\n";
} else {
    echo "✗ Nachrichten module not found\n\n";
}

// Update Termine Module
echo "Updating Termine module...\n";

$termineModule = Module::query()->where('setting', 'Termine')->first();

if ($termineModule) {
    $options = $termineModule->options;

    // Remove home-view-top
    if (isset($options['home-view-top'])) {
        unset($options['home-view-top']);
        echo "  - Removed home-view-top\n";
    }

    // Add or update navigation
    if (! isset($options['nav'])) {
        $options['nav'] = [
            'name' => 'Termine',
            'link' => 'termin',
            'icon' => 'far fa-calendar-alt',
            'bottom-nav' => 'true',
        ];
        echo "  - Added navigation\n";
    } else {
        $options['nav']['link'] = 'termin';
        $options['nav']['bottom-nav'] = 'true';
        echo "  - Updated nav link to /termin\n";
    }

    $termineModule->options = $options;
    $termineModule->save();

    echo "✓ Termine module updated successfully\n\n";
} else {
    echo "✗ Termine module not found\n\n";
}

echo "Done! Please clear cache with: php artisan cache:clear\n";
