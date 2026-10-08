<?php

namespace Tests\Feature\Familienmodell;

use App\Mail\KrankmeldungInfoMail;
use App\Model\Child;
use App\Model\Krankmeldungen;
use App\Model\Notification as AppNotification;
use App\Services\Family\FamilyResolver;
use App\Services\Krankmeldungen\GuardianNotifier;
use App\Settings\NotifySetting;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Krankmeldung: weitere Berechtigte des Kindes werden informiert (Spezifikation 2.4).
 */
class KrankmeldungInfoTest extends TestCase
{
    use BuildsFamilies;

    #[Test]
    public function other_guardians_with_health_access_are_informed(): void
    {
        Mail::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
        $mother = $this->makeParent();
        $father = $this->makeParent();
        $grandma = $this->makeParent();
        $this->familyOf($mother);
        $this->familyOf($father);
        $child = Child::factory()->create();
        $child->parents()->attach([$mother->id, $father->id]);
        $child->parents()->attach($grandma->id, ['relation' => 'grandparent', 'has_custody' => false, 'can_manage' => false]);

        $krankmeldung = Krankmeldungen::factory()->create([
            'users_id' => $mother->id, 'child_id' => $child->id, 'start' => now(), 'ende' => now()->addDay(),
        ]);

        $informed = app(GuardianNotifier::class)->notifyOthers($krankmeldung, $mother);

        // Vater ja, Großmutter ohne Zugriff auf Gesundheitsdaten nein, Meldende nicht
        $this->assertSame([$father->id], $informed->pluck('id')->all());
        $this->assertSame(1, AppNotification::where('user_id', $father->id)->count());
        Mail::assertQueued(KrankmeldungInfoMail::class, fn ($mail) => $mail->hasTo($father->email));
    }

    #[Test]
    public function can_be_disabled(): void
    {
        Mail::fake();
        $settings = app(NotifySetting::class);
        $settings->krankmeldung_notify_guardians = false;
        $settings->save();

        [$a, $b, $child] = $this->coupleWithSharedChild();
        $krankmeldung = Krankmeldungen::factory()->create([
            'users_id' => $a->id, 'child_id' => $child->id, 'start' => now(), 'ende' => now()->addDay(),
        ]);

        $this->assertCount(0, app(GuardianNotifier::class)->notifyOthers($krankmeldung, $a));
        Mail::assertNothingQueued();
    }
}
