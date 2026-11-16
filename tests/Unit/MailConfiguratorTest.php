<?php

namespace Tests\Unit;

use App\Settings\EmailSetting;
use App\Support\MailConfigurator;
use Tests\TestCase;

class MailConfiguratorTest extends TestCase
{
    private function settings(array $values): EmailSetting
    {
        $settings = (new \ReflectionClass(EmailSetting::class))->newInstanceWithoutConstructor();
        foreach ($values as $key => $value) {
            $settings->{$key} = $value;
        }

        return $settings;
    }

    public function test_smtp_settings_override_sendmail_mailer_from_env(): void
    {
        config([
            'mail.default' => 'sendmail',
            'mail.mailers.smtp.url' => 'smtp://localhost:25',
        ]);

        MailConfigurator::apply($this->settings([
            'mail_server' => 'mail.example.org',
            'mail_port' => '587',
            'mail_username' => 'user',
            'mail_password' => 'secret',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'info@example.org',
            'mail_from_name' => 'Schule',
        ]));

        $this->assertSame('smtp', config('mail.default'));
        $this->assertNull(config('mail.mailers.smtp.url'));
        $this->assertSame('mail.example.org', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('smtp', config('mail.mailers.smtp.scheme'));
        $this->assertSame('info@example.org', config('mail.from.address'));
    }

    public function test_without_smtp_server_the_env_mailer_is_kept(): void
    {
        config(['mail.default' => 'sendmail']);

        MailConfigurator::apply($this->settings(['mail_server' => null]));

        $this->assertSame('sendmail', config('mail.default'));
    }

    public function test_encryption_is_mapped_to_symfony_scheme(): void
    {
        $this->assertSame('smtp', MailConfigurator::schemeFor('tls'));
        $this->assertSame('smtps', MailConfigurator::schemeFor('SSL'));
        $this->assertNull(MailConfigurator::schemeFor('none'));
    }
}
