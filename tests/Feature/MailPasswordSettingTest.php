<?php

namespace Tests\Feature;

use App\Settings\EmailSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelSettings\Support\Crypto;
use Tests\TestCase;

/**
 * Das Mail-Passwort (Settings `email.mail_password`) muss sich immer entschlüsseln
 * lassen – auch ohne MAIL_PASSWORD in der .env – und wird von
 * 2026_11_03_000002_repair_mail_password_setting in allen bekannten Fehlerzuständen repariert.
 */
class MailPasswordSettingTest extends TestCase
{
    use RefreshDatabase;

    private function setPayload(mixed $payload): void
    {
        DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->update(['payload' => json_encode($payload)]);
    }

    private function repair(): void
    {
        (require database_path('migrations/2026_11_03_000002_repair_mail_password_setting.php'))->up();
    }

    private function storedPassword(): ?string
    {
        $payload = DB::table('settings')
            ->where('group', 'email')
            ->where('name', 'mail_password')
            ->value('payload');

        return Crypto::decrypt(json_decode($payload, true));
    }

    /** @test */
    public function settings_load_after_a_fresh_migration(): void
    {
        $this->assertTrue(
            is_null(app(EmailSetting::class)->mail_password) || is_string(app(EmailSetting::class)->mail_password)
        );
    }

    /** @test */
    public function placeholder_timestamp_becomes_empty(): void
    {
        $this->setPayload(1730000000);

        $this->repair();

        $this->assertNull($this->storedPassword());
    }

    /** @test */
    public function plain_text_gets_encrypted(): void
    {
        $this->setPayload('geheim');

        $this->repair();

        $this->assertSame('geheim', $this->storedPassword());
    }

    /** @test */
    public function literal_null_string_becomes_empty(): void
    {
        $this->setPayload('null');

        $this->repair();

        $this->assertNull($this->storedPassword());
    }

    /** @test */
    public function encrypt_string_format_gets_reencrypted(): void
    {
        $this->setPayload(Crypt::encryptString('geheim'));

        $this->repair();

        $this->assertSame('geheim', $this->storedPassword());
    }

    /** @test */
    public function double_serialized_value_is_unwrapped(): void
    {
        // Zustand nach 2026_03_10 auf einer Neuinstallation: encrypt(serialize('geheim'))
        $this->setPayload(Crypt::encrypt(serialize('geheim')));

        $this->repair();

        $this->assertSame('geheim', $this->storedPassword());
    }

    /** @test */
    public function correct_value_stays_unchanged(): void
    {
        $encrypted = Crypt::encrypt('geheim');
        $this->setPayload($encrypted);

        $this->repair();

        $this->assertSame('geheim', $this->storedPassword());
        $this->assertSame(
            json_encode($encrypted),
            DB::table('settings')->where('group', 'email')->where('name', 'mail_password')->value('payload')
        );
    }

    /** @test */
    public function empty_value_stays_empty(): void
    {
        $this->setPayload(null);

        $this->repair();

        $this->assertNull($this->storedPassword());
    }

    /** @test */
    public function the_2026_03_10_fix_leaves_correct_values_alone(): void
    {
        $this->setPayload(Crypt::encrypt('geheim'));

        (require database_path('migrations/2026_03_10_091901_fix_mail_password_encryption_in_settings.php'))->up();

        $this->assertSame('geheim', $this->storedPassword());
    }
}
