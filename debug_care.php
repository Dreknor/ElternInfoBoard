<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$cs = app(\App\Settings\CareSetting::class);
echo 'groups_list: ' . json_encode($cs->groups_list) . PHP_EOL;
echo 'class_list: ' . json_encode($cs->class_list) . PHP_EOL;

// Auch die Kinder eines Users prüfen
$users = \App\Model\User::has('children_rel')->take(3)->get();
foreach ($users as $user) {
    $children = $user->children();
    echo PHP_EOL . "User #{$user->id} ({$user->name}): " . ($children ? $children->count() : 'null') . " Kinder" . PHP_EOL;
    if ($children && $children->count() > 0) {
        foreach ($children as $child) {
            echo "  - {$child->first_name} {$child->last_name}: group_id={$child->group_id}, class_id={$child->class_id}" . PHP_EOL;
        }
    }
}

