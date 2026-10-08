<?php

namespace Tests\Feature\Familienmodell;

use App\Jobs\ProcessRemindersJob;
use App\Model\Child;
use App\Model\Group;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Model\ReminderLog;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\ReadReceiptStatusService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Lesebestätigung je Familie (bisher), je Person oder je Kind (Spezifikation 2.3).
 */
class LesebestaetigungScopeTest extends TestCase
{
    use BuildsFamilies;

    private User $mother;

    private User $stepfather;

    private User $father;

    private Group $group;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Notification::fake();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);

        $this->group = Group::factory()->create(['protected' => false]);
        $this->mother = $this->makeParent();
        $this->stepfather = $this->makeParent();
        $this->father = $this->makeParent();
        // Mutter + Stiefvater = eine Familie, leiblicher Vater = eigene Familie
        $this->familyOf($this->mother, $this->stepfather);
        $this->familyOf($this->father);
        foreach ([$this->mother, $this->stepfather, $this->father] as $user) {
            $user->groups()->attach($this->group->id);
        }
        $child = Child::create(['first_name' => 'Mia', 'last_name' => 'M', 'class_id' => $this->group->id]);
        $child->parents()->attach([$this->mother->id, $this->father->id]);
        $child->parents()->attach($this->stepfather->id, ['relation' => 'partner', 'has_custody' => false]);
    }

    private function postWithScope(string $scope): Post
    {
        $post = Post::factory()->create([
            'released' => 1, 'author' => $this->makeParent()->id, 'archiv_ab' => now()->addDays(10),
            'read_receipt' => true, 'read_receipt_deadline' => now()->addDay(), 'read_receipt_scope' => $scope,
        ]);
        $post->groups()->attach($this->group->id);

        return $post->fresh();
    }

    private function confirm(User $user, Post $post): void
    {
        ReadReceipts::create(['post_id' => $post->id, 'user_id' => $user->id, 'confirmed_at' => now()]);
    }

    #[Test]
    public function family_scope_counts_family_members_only(): void
    {
        $post = $this->postWithScope('family');
        $this->confirm($this->mother, $post);
        $service = app(ReadReceiptStatusService::class);

        $this->assertTrue($service->isSatisfied($this->stepfather, $post));
        $this->assertFalse($service->isSatisfied($this->father, $post));
    }

    #[Test]
    public function person_scope_requires_everyone(): void
    {
        $post = $this->postWithScope('person');
        $this->confirm($this->mother, $post);
        $service = app(ReadReceiptStatusService::class);

        $this->assertTrue($service->isSatisfied($this->mother, $post));
        $this->assertFalse($service->isSatisfied($this->stepfather, $post));
    }

    #[Test]
    public function child_scope_is_satisfied_by_any_guardian_across_families(): void
    {
        $post = $this->postWithScope('child');
        $this->confirm($this->father, $post);
        $service = app(ReadReceiptStatusService::class);

        // Bestätigung des getrennt lebenden Vaters gilt für Mias Mutter und Stiefvater
        $this->assertTrue($service->isSatisfied($this->mother, $post));
        $this->assertTrue($service->isSatisfied($this->stepfather, $post));

        (new ProcessRemindersJob)->handle(app(\App\Settings\ReminderSetting::class));
        $this->assertSame(0, ReminderLog::where('remindable_type', Post::class)->count());
    }

    #[Test]
    public function child_scope_acts_as_family_scope_in_legacy_mode(): void
    {
        $this->useResolver(FamilyResolver::MODE_LEGACY);
        $post = $this->postWithScope('child');
        $this->confirm($this->father, $post);

        $this->assertSame('family', app(ReadReceiptStatusService::class)->effectiveScope($post));
        $this->assertFalse(app(ReadReceiptStatusService::class)->isSatisfied($this->mother, $post));
    }
}
