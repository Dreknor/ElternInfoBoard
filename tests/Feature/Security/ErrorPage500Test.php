<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ErrorPage500Test extends TestCase
{
    public function test_500_seite_ohne_sentry_dsn_enthaelt_keinen_drittanbieter_verweis(): void
    {
        config(['sentry.dsn' => null]);

        $html = view('errors.500')->render();

        $this->assertStringContainsString('Interner Serverfehler', $html);
        $this->assertStringNotContainsString('sentry.io', $html);
        $this->assertStringNotContainsString('sentry-cdn', $html);
        $this->assertStringNotContainsString('187901', $html);
    }

    public function test_500_seite_mit_dsn_aber_ohne_event_id_laedt_kein_skript(): void
    {
        config(['sentry.dsn' => 'https://key@example.invalid/1']);

        $html = view('errors.500')->render();

        $this->assertStringNotContainsString('sentry-cdn', $html);
    }

    public function test_500_seite_mit_dsn_und_event_id_nutzt_konfigurierte_dsn(): void
    {
        config(['sentry.dsn' => 'https://key@example.invalid/1']);
        $hub = new class {
            public function getLastEventId(): string
            {
                return 'abc123';
            }
        };
        $this->app->instance('sentry', $hub);

        $html = view('errors.500')->render();

        $this->assertStringContainsString('sentry-cdn', $html);
        $this->assertStringContainsString('key@example.invalid', str_replace('\/', '/', $html));
        $this->assertStringNotContainsString('908e7b8dd4294fc98a0d47b13e8da008', $html);
    }
}
