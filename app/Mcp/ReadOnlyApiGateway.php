<?php

namespace App\Mcp;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Führt einen lesenden Aufruf der App-API im Kontext des angemeldeten Nutzers aus.
 *
 * Der Aufruf läuft als interne Anfrage durch den Router – inklusive aller Route-Middleware
 * (Passwortwechsel, Berechtigungen, Module) und der Policies der Controller. Der MCP-Server sieht
 * damit exakt das, was der Nutzer auch über API oder Web-Oberfläche sehen würde.
 * Es werden ausschließlich GET-Routen unter `api/` zugelassen.
 */
class ReadOnlyApiGateway
{
    public function __construct(private readonly Router $router, private readonly ExceptionHandler $handler) {}

    /**
     * @param  array<string, scalar>  $query
     * @return array{status: int, body: string}
     */
    public function get(Authenticatable $user, string $uri, array $query = []): array
    {
        $request = Request::create('/'.ltrim($uri, '/'), 'GET', $query, server: ['HTTP_ACCEPT' => 'application/json']);

        $this->assertReadOnlyApiRoute($request);
        // Routen mit Seiteneffekten beim Lesen (z. B. Gelesen-Markierung) überspringen diese bei diesem Attribut.
        $request->attributes->set('read_only', true);

        $original = app('request');
        $guard = Auth::guard('sanctum');
        $previousUser = $guard->hasUser() ? $guard->user() : null;
        $previousDefault = Auth::getDefaultDriver();

        app()->instance('request', $request);
        Facade::clearResolvedInstance('request');
        $guard->setUser($user);
        $request->setUserResolver(fn () => $user);

        // Zweite Sicherung neben „nur GET“: Was eine Route beim Lesen doch in die Datenbank schreibt,
        // wird verworfen. So bleibt der MCP-Server auch dann ohne Schreibzugriff, wenn eine GET-Route
        // später eine Nebenwirkung bekommt.
        $connection = DB::connection();
        $connection->beginTransaction();

        try {
            $response = $this->router->dispatch($request);
        } catch (Throwable $e) {
            $this->handler->report($e);
            $response = $this->handler->render($request, $e);
        } finally {
            $connection->rollBack();
            app()->instance('request', $original);
            Facade::clearResolvedInstance('request');
            Auth::shouldUse($previousDefault);
            $previousUser ? $guard->setUser($previousUser) : $guard->forgetUser();
        }

        return ['status' => $response->getStatusCode(), 'body' => $this->content($response)];
    }

    private function assertReadOnlyApiRoute(Request $request): void
    {
        $route = $this->router->getRoutes()->match($request);

        if (! str_starts_with($route->uri(), 'api/') || array_diff($route->methods(), ['GET', 'HEAD']) !== []) {
            throw new \LogicException('Nur lesende API-Routen sind erlaubt.');
        }
    }

    private function content(Response $response): string
    {
        return (string) ($response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            || $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            ? '{"message":"Dieser Inhalt ist über MCP nicht abrufbar."}'
            : $response->getContent());
    }
}

