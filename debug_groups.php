<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Alle Gruppen auflisten
echo "=== Alle Gruppen ===" . PHP_EOL;
$groups = \App\Model\Group::withoutGlobalScopes()->orderBy('id')->get(['id', 'name', 'owner_id']);
foreach ($groups as $g) {
    echo "  ID={$g->id}  name={$g->name}  owner={$g->owner_id}" . PHP_EOL;
}

// Settings-Tabelle prüfen
echo PHP_EOL . "=== Settings (care.*) ===" . PHP_EOL;
$settings = \DB::table('settings')->where('group', 'care')->get();
foreach ($settings as $s) {
    echo "  {$s->name} = {$s->payload}" . PHP_EOL;
}

