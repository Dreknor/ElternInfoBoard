<?php

namespace Tests\Feature\Schuelerkonten;

use App\Http\Middleware\RestrictStudentAccounts;
use App\Model\Child;
use App\Model\Group;
use App\Services\Students\StudentAccountService;
use App\Support\StudentAccounts;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Ein Schülerkonto erreicht ausschließlich die freigegebenen Routen (§5.1, Paket S-03).
 * Geht alle registrierten Routen durch, auch künftige: neue Routen sind automatisch gesperrt.
 */
class StudentRouteSweepTest extends TestCase
{
    use ActivatesModules;

    public function test_student_reaches_only_allowed_routes(): void
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        $class = Group::factory()->create();
        $child = Child::factory()->create(['class_id' => $class->id]);
        [$student] = app(StudentAccountService::class)->createForChild($child);

        // Drosselung würde mit 429 antworten und echte Antworten verdecken
        $this->withoutMiddleware([\Illuminate\Routing\Middleware\ThrottleRequests::class, \Illuminate\Routing\Middleware\ThrottleRequestsWithRedis::class]);

        // Harmlose, nicht personenbezogene Routen
        $public = ['sanctum.csrf-cookie'];

        $reached = [];
        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if ($name !== null && (RestrictStudentAccounts::allowedRouteName($name) || in_array($name, $public, true))) {
                continue;
            }
            if (str_starts_with($route->uri(), 'livewire/livewire') || $route->uri() === 'up') {
                continue; // Skriptdatei, Health-Check
            }

            $uri = preg_replace('/\{[^}]+\?\}/', '', $route->uri());
            $uri = '/'.ltrim(preg_replace('/\{[^}]+\}/', '1', $uri), '/');

            foreach (array_diff($route->methods(), ['HEAD', 'OPTIONS']) as $method) {
                $this->app['auth']->forgetGuards();
                $this->app['auth']->shouldUse('web');
                $response = $this->actingAs($student)->call($method, $uri);
                $status = $response->getStatusCode();

                // Erlaubt: nicht gefunden, verboten, nicht angemeldet, Umleitung auf den Schülerbereich/Login
                $location = (string) $response->headers->get('Location');
                $ok = in_array($status, [401, 403, 404, 405, 419], true)
                    || ($status >= 300 && $status < 400 && (str_contains($location, '/schueler') || str_contains($location, '/login')));

                if (! $ok) {
                    $reached[] = "{$method} {$uri} ({$name}) → {$status} {$location}";
                }
            }
        }

        $this->assertSame([], $reached, "Schülerkonto erreicht gesperrte Routen:\n".implode("\n", $reached));
    }
}
