<?php

namespace Tests\Feature;

use App\Http\Controllers\NachrichtenController;
use App\Mail\AktuelleInformationen;
use App\Model\Group;
use App\Model\Post;
use App\Model\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * E-Mail-Zusammenfassung: Abschnitte ohne Modulrecht werden als leere
 * Collection übergeben – das darf den Versand nicht abbrechen
 * (TypeError „Argument #4 ($listen) must be of type Eloquent\Collection|array“).
 */
class NewsletterDigestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function mail_accepts_plain_collections_and_arrays(): void
    {
        $mail = new AktuelleInformationen(collect(), 'Eltern', [], collect(), collect(), [], null);

        $this->assertInstanceOf(AktuelleInformationen::class, $mail);
        $mail->render();
    }

    /** @test */
    public function digest_is_queued_for_a_user_without_listen_and_termine_modules(): void
    {
        Mail::fake();

        // Ohne Module „Listen“/„Termine“ liefert NotificationAccess für diese Abschnitte „kein Recht“
        \App\Model\Module::query()->whereIn('setting', ['Listen', 'Termine'])->delete();

        $group = Group::factory()->create(['owner_id' => null]);
        $user = User::factory()->create(['benachrichtigung' => 'weekly', 'lastEmail' => now()->subDay(), 'changePassword' => false]);
        $user->groups()->attach($group);

        $post = Post::factory()->create([
            'released' => 1, 'external' => 0, 'archiv_ab' => now()->addWeek(),
            'confidential' => false, 'header' => 'Neue Info',
        ]);
        $post->groups()->attach($group);

        app(NachrichtenController::class)->email('weekly', $user->id);

        Mail::assertQueued(AktuelleInformationen::class, fn ($m) => $m->hasTo($user->email));
    }
}
