<?php

return [

    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    // capture release as git sha
    // 'release' => trim(exec('git --git-dir ' . base_path('.git') . ' log --pretty="%h" -n1 HEAD')),

    'breadcrumbs' => [
        // SQL-Parameter NICHT an Sentry senden (DSGVO: enthalten E-Mails, Namen, etc.)
        // Nur bei lokalem Debugging aktivieren: SENTRY_SQL_BINDINGS=true in .env setzen
        'sql_bindings' => env('SENTRY_SQL_BINDINGS', false),
    ],

];
