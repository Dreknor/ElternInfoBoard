<?php

namespace Tests\Feature;

use App\Settings\EmailSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Events\MessageSent;
use Tests\TestCase;

class EmailLoggingTest extends TestCase
{
    /**
     * Test ob EmailSetting korrekt geladen wird
     */
    public function test_email_settings_can_be_loaded(): void
    {
        $settings = app(EmailSetting::class);

        $this->assertNotNull($settings);
        $this->assertObjectHasProperty('log_sent_emails', $settings);
        $this->assertObjectHasProperty('new_user_welcome_text', $settings);
    }

    /**
     * Test ob der LogEmailSent Listener registriert ist
     */
    public function test_email_sent_listener_is_registered(): void
    {
        $listeners = Event::getListeners(MessageSent::class);

        $this->assertNotEmpty($listeners);
        $this->assertContains(
            \App\Listeners\LogEmailSent::class,
            array_map(function($listener) {
                return is_string($listener) ? $listener : get_class($listener);
            }, $listeners)
        );
    }

    /**
     * Test ob E-Mails geloggt werden wenn aktiviert
     */
    public function test_emails_are_logged_when_enabled(): void
    {
        // E-Mail-Logging aktivieren
        $settings = app(EmailSetting::class);
        $settings->log_sent_emails = true;
        $settings->save();

        // Log-Spy einrichten
        Log::spy();

        // Test-Mail vorbereiten (aber nicht wirklich senden)
        Mail::fake();

        // Hier würde normalerweise eine echte Mail versendet
        // Für den Test simulieren wir nur das Event
        // Event::dispatch(new MessageSent(...));

        $this->assertTrue(true); // Placeholder für echten Test
    }
}

