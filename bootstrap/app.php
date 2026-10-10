<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Jobs\SendFreeScoutTicket;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \Barryvdh\DomPDF\ServiceProvider::class,
        \DevDojo\LaravelReactions\Providers\ReactionsServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/home');

        $middleware->web([
            \App\Http\Middleware\CheckNewsForUser::class,
            \App\Http\Middleware\LastOnlineAt::class,
            \App\Http\Middleware\CheckUserActive::class,
            \App\Http\Middleware\ApplyTheme::class,
            \App\Http\Middleware\EnsureUserHasEmail::class,
        ]);

        $middleware->throttleApi();

        // Wartungsmodus-Bypass wird vom Online-Updater gesetzt und vor dem
        // Entschlüsseln der Cookies geprüft – daher unverschlüsselt lassen.
        $middleware->encryptCookies(except: ['laravel_maintenance']);

        $middleware->alias([
            'mark_passwordless_login' => \App\Http\Middleware\MarkPasswordlessLogin::class,
            'password_expired' => \App\Http\Middleware\PasswordExpired::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'idempotency' => \App\Http\Middleware\IdempotencyKey::class,
            'etag' => \App\Http\Middleware\ETagResponses::class,
            'api.password' => \App\Http\Middleware\ApiPasswordChangeRequired::class,
            'module' => \App\Http\Middleware\EnsureModuleActive::class,
        ]);

        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\Authenticate::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \Illuminate\Auth\Middleware\Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
         | AuthenticationException → JSON-Response für API-Requests
         */
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nicht authentifiziert.',
                ], 401);
            }
        });

        /*
         | App-API v1: deutsche Meldungen für fehlende Rechte und unbekannte Datensätze.
         */
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            if (! $request->is('api/v1/*')) {
                return null;
            }
            $status = $e->getStatusCode();
            $message = $e->getMessage();
            $defaults = [
                403 => 'Dafür fehlt Ihnen die Berechtigung.',
                404 => 'Nicht gefunden.',
                409 => 'Das ist bereits erledigt oder wurde inzwischen geändert.',
                410 => 'Die Frist ist abgelaufen.',
                422 => 'Bitte überprüfen Sie Ihre Eingaben.',
            ];
            // Englische Standardtexte (Policies, Model-Binding) durch deutsche ersetzen.
            if ($message === '' || $message === 'This action is unauthorized.' || str_starts_with($message, 'No query results')) {
                $message = $defaults[$status] ?? 'Die Anfrage konnte nicht ausgeführt werden.';
            }

            return response()->json(['message' => $message], $status, $e->getHeaders());
        });

        /*
         | Ungeplante Server-Exceptions (500) → automatisches FreeScout-Ticket.
         | Ausgenommen: Validierungsfehler, Auth-Fehler, HTTP-Ausnahmen (404, 403, …)
         | und bekannte "harmlose" Ausnahmen, die keinen Ticket-Spam erzeugen sollen.
         */
        $exceptions->report(function (\Throwable $e) {
            // Nur echte, ungeplante Fehler weiterleiten
            $ignored = [
                \Illuminate\Auth\AuthenticationException::class,
                \Illuminate\Auth\Access\AuthorizationException::class,
                \Illuminate\Validation\ValidationException::class,
                \Symfony\Component\HttpKernel\Exception\HttpException::class,
                \Illuminate\Database\Eloquent\ModelNotFoundException::class,
                \Illuminate\Session\TokenMismatchException::class,
            ];

            foreach ($ignored as $class) {
                if ($e instanceof $class) {
                    return false; // normales Logging, kein Ticket
                }
            }

            try {
                $user  = auth()->user();
                /** @var \App\Model\User|null $typedUser */
                $typedUser = $user instanceof \App\Model\User ? $user : null;
                $name  = $typedUser?->name  ?? 'System (nicht eingeloggt)';
                $email = $typedUser?->email ?? config('mail.from.address', 'system@localhost');
                $role  = $typedUser?->roles->first()?->name ?? null;
                $url   = request()?->fullUrl() ?? 'n/a';

                $message = "**Exception:** " . get_class($e) . "\n"
                         . "**Meldung:** "  . $e->getMessage() . "\n\n"
                         . "**Datei:** "    . $e->getFile() . ':' . $e->getLine() . "\n\n"
                         . "**Stack Trace (gekürzt):**\n```\n"
                         . substr($e->getTraceAsString(), 0, 2000)
                         . "\n```";

                SendFreeScoutTicket::dispatchTicket([
                    'subject'    => '[500] ' . get_class($e) . ' – ' . substr($e->getMessage(), 0, 120),
                    'message'    => $message,
                    'screenshot' => null,
                    'page_url'   => $url,
                    'user_name'  => $name,
                    'user_email' => $email,
                    'user_role'  => $role,
                ]);
            } catch (\Throwable $dispatchError) {
                // Logging-Fallback – verhindert rekursive Fehler
                \Illuminate\Support\Facades\Log::error(
                    'SendFreeScoutTicket konnte nicht dispatched werden: ' . $dispatchError->getMessage()
                );
            }

            return false; // normales Logging zusätzlich ausführen
        });
    })->create();
