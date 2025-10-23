<?php

namespace Tests\Feature;

use App\Model\Mail;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail as MailFacade;
use Tests\TestCase;

/**
 * Feature-Tests für Mail-System
 */
class MailSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function mail_can_be_stored_in_database()
    {
        $user = User::factory()->create();

        $mail = Mail::factory()->create([
            'senders_id' => $user->id,
            'subject' => 'Test E-Mail',
            'text' => 'Dies ist der E-Mail-Inhalt',
        ]);

        $this->assertDatabaseHas('mails', [
            'id' => $mail->id,
            'senders_id' => $user->id,
            'subject' => 'Test E-Mail',
        ]);
    }

    /**
     * @test
     */
    public function mail_belongs_to_author()
    {
        $user = User::factory()->create();
        $mail = Mail::factory()->create(['senders_id' => $user->id]);

        $this->assertInstanceOf(User::class, $mail->sender);
        $this->assertEquals($user->id, $mail->sender->id);
    }

    /**
     * @test
     */
    public function user_receives_copy_when_sendCopy_is_enabled()
    {
        $user = User::factory()->create(['sendCopy' => true]);

        $this->assertTrue($user->sendCopy);
    }

    /**
     * @test
     */
    public function user_can_have_public_email()
    {
        $user = User::factory()->create([
            'email' => 'private@example.com',
            'publicMail' => 'public@example.com',
        ]);

        $this->assertEquals('public@example.com', $user->publicMail);
        $this->assertNotEquals($user->email, $user->publicMail);
    }

    /**
     * @test
     */
    public function last_email_timestamp_is_tracked()
    {
        $user = User::factory()->create();

        $user->update(['lastEmail' => now()]);

        $this->assertNotNull($user->lastEmail);
        $this->assertInstanceOf(\DateTime::class, $user->lastEmail);
    }
}

