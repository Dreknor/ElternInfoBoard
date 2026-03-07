<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Environment: " . config('app.env') . PHP_EOL;
echo "Default DB Connection: " . config('database.default') . PHP_EOL;
echo "Settings Repository Connection: " . config('settings.repositories.database.connection') . PHP_EOL;

try {
    $pdo = DB::connection()->getPdo();
    echo "PDO Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
    echo "Database: " . DB::connection()->getDatabaseName() . PHP_EOL;

    // Test User query
    $email = 'daniel.roehrich@esz-radebeul.de';
    $user = DB::table('users')->where('email', $email)->first();
    echo "User found: " . ($user ? "YES (ID: {$user->id})" : "NO") . PHP_EOL;

    // List all users with similar email
    $users = DB::table('users')->where('email', 'like', '%daniel.roehrich%')->get();
    echo "Users matching 'daniel.roehrich': " . $users->count() . PHP_EOL;
    foreach ($users as $u) {
        echo "  - {$u->id}: {$u->email}" . PHP_EOL;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}

