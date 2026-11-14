<?php

namespace Tests\Feature;

use App\Model\Group;
use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Eintragungslisten: Listen-Ersteller und Verwaltung („edit terminliste“) können
 * nicht reservierte Einträge kopieren.
 */
class ListenEintragCopyTest extends TestCase
{
    use BuildsFamilies;

    private User $owner;

    private Liste $liste;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->owner = $this->makeParent(['changePassword' => false]);
        $this->liste = Liste::create([
            'listenname' => 'Sommerfest',
            'type' => 'eintrag',
            'besitzer' => $this->owner->id,
            'visible_for_all' => true,
            'active' => true,
            'ende' => now()->addWeek(),
            'multiple' => true,
            'make_new_entry' => false,
        ]);

        $group = Group::withoutGlobalScopes()->create(['name' => 'Klasse 3a', 'protected' => false]);
        DB::table('group_user')->insert(['group_id' => $group->id, 'user_id' => $this->owner->id]);
        DB::table('group_listen')->insert(['group_id' => $group->id, 'liste_id' => $this->liste->id]);
    }

    private function eintrag(array $attributes = []): Listen_Eintragungen
    {
        return Listen_Eintragungen::create(array_merge([
            'listen_id' => $this->liste->id,
            'eintragung' => 'Kuchen',
            'created_by' => $this->owner->id,
        ], $attributes));
    }

    #[Test]
    public function owner_can_copy_free_entry(): void
    {
        $eintrag = $this->eintrag();

        $this->actingAs($this->owner)->post("listen/eintragungen/{$eintrag->id}/copy")->assertRedirect();

        $copies = Listen_Eintragungen::where('listen_id', $this->liste->id)->where('eintragung', 'Kuchen')->get();
        $this->assertCount(2, $copies);
        $this->assertNull($copies->last()->user_id);
    }

    #[Test]
    public function admin_can_copy_free_entry(): void
    {
        $admin = $this->makeParent(['changePassword' => false]);
        Permission::findOrCreate('edit terminliste', 'web');
        $admin->givePermissionTo('edit terminliste');

        $eintrag = $this->eintrag();
        $this->actingAs($admin)->post("listen/eintragungen/{$eintrag->id}/copy")->assertRedirect();

        $this->assertSame(2, Listen_Eintragungen::where('listen_id', $this->liste->id)->count());
    }

    #[Test]
    public function other_user_cannot_copy(): void
    {
        $eintrag = $this->eintrag();
        $other = $this->makeParent(['changePassword' => false]);

        $this->actingAs($other)->post("listen/eintragungen/{$eintrag->id}/copy")->assertForbidden();
        $this->assertSame(1, Listen_Eintragungen::where('listen_id', $this->liste->id)->count());
    }

    #[Test]
    public function reserved_entry_cannot_be_copied(): void
    {
        $eintrag = $this->eintrag(['user_id' => $this->makeParent()->id, 'bemerkung' => 'Apfelkuchen']);

        $this->actingAs($this->owner)->post("listen/eintragungen/{$eintrag->id}/copy")->assertRedirect();
        $this->assertSame(1, Listen_Eintragungen::where('listen_id', $this->liste->id)->count());
    }

    #[Test]
    public function copy_button_only_visible_for_owner(): void
    {
        $eintrag = $this->eintrag();
        $url = route('listen.eintragungen.copy', $eintrag->id);

        $this->actingAs($this->owner)->get("listen/{$this->liste->id}")->assertSee($url, false);

        $other = $this->makeParent(['changePassword' => false]);
        DB::table('group_user')->insert(['group_id' => Group::withoutGlobalScopes()->first()->id, 'user_id' => $other->id]);
        $this->actingAs($other)->get("listen/{$this->liste->id}")->assertOk()->assertDontSee($url, false);
    }
}
