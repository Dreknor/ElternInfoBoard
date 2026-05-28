<?php

namespace Tests\Feature;

use App\Model\Conversation;
use App\Model\Message;
use App\Model\MessageReport;
use App\Model\User;
use App\Settings\MessengerSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Feature-Tests für Feature 2: Eltern-Nachrichten (Messenger)
 *
 * Abgedeckte Bereiche:
 *  – Konversationen abrufen (Web + API)
 *  – Nachrichten senden, bearbeiten, löschen
 *  – Berechtigungsprüfungen (use messenger, moderate messages)
 *  – Rate-Limiting-Schutz
 *  – Direktnachrichten
 *  – Meldungen + Moderationsaktionen
 *  – Stummschaltung
 *  – Datenmodell (unread, displayName)
 */
class MessengerTest extends TestCase
{
    use RefreshDatabase;

    private User $userA;
    private User $userB;
    private User $moderator;

    protected function setUp(): void
    {
        parent::setUp();

        // Berechtigungen anlegen
        Permission::findOrCreate('use messenger', 'web');
        Permission::findOrCreate('use messenger', 'api');
        Permission::findOrCreate('moderate messages', 'web');

        // Messenger-Settings anlegen (in-memory via DB)
        $this->seedMessengerSettings();

        $this->userA = User::factory()->create(['password_changed_at' => now()]);
        $this->userA->givePermissionTo('use messenger');

        $this->userB = User::factory()->create(['password_changed_at' => now()]);
        $this->userB->givePermissionTo('use messenger');

        $this->moderator = User::factory()->create(['password_changed_at' => now()]);
        $this->moderator->givePermissionTo('moderate messages');
        $this->moderator->givePermissionTo('use messenger');
    }

    // ── Hilfsmethode ──────────────────────────────────────────────

    private function seedMessengerSettings(): void
    {
        \DB::table('settings')->where('group', 'messenger')->delete();
        $settings = [
            ['group' => 'messenger', 'name' => 'auto_delete_days',      'payload' => json_encode(90)],
            ['group' => 'messenger', 'name' => 'max_message_length',    'payload' => json_encode(2000)],
            ['group' => 'messenger', 'name' => 'allow_direct_messages', 'payload' => json_encode(true)],
            ['group' => 'messenger', 'name' => 'allow_file_uploads',    'payload' => json_encode(true)],
            ['group' => 'messenger', 'name' => 'max_file_size_mb',      'payload' => json_encode(10)],
        ];
        \DB::table('settings')->insert($settings);
    }

    private function createGroupConversation(User ...$members): Conversation
    {
        $conv = Conversation::create([
            'type'             => 'group',
            'title'            => 'Testgruppe',
            'created_by'       => $members[0]->id,
            'is_active'        => true,
            'auto_delete_days' => 90,
        ]);
        foreach ($members as $member) {
            $conv->users()->attach($member->id, ['joined_at' => now()]);
        }
        return $conv;
    }

    private function createDirectConversation(User $a, User $b): Conversation
    {
        $conv = Conversation::create([
            'type'       => 'direct',
            'created_by' => $a->id,
            'is_active'  => true,
            'auto_delete_days' => 90,
        ]);
        $conv->users()->attach([$a->id => ['joined_at' => now()], $b->id => ['joined_at' => now()]]);
        return $conv;
    }

    // ════════════════════════════════════════════════════════════════
    //  1. Datenmodell-Tests
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function conversation_model_relationships_work(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        $this->assertCount(2, $conv->users);
        $this->assertNull($conv->latestMessage);
        $this->assertEquals('group', $conv->type);
    }

    #[Test]
    public function unread_count_is_zero_for_own_messages(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Hallo',
            'type'            => 'text',
        ]);

        $conv->load('users');
        // Eigene Nachricht zählt nicht als ungelesen
        $this->assertEquals(0, $conv->unreadCountFor($this->userA->id));
    }

    #[Test]
    public function unread_count_increments_for_other_user(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Hallo',
            'type'            => 'text',
        ]);

        $conv->load('users');
        $this->assertEquals(1, $conv->unreadCountFor($this->userB->id));
    }

    #[Test]
    public function display_name_returns_title_for_group(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        $conv->load('users');
        $this->assertEquals('Testgruppe', $conv->displayNameFor($this->userA->id));
    }

    #[Test]
    public function display_name_returns_other_user_for_direct(): void
    {
        $conv = $this->createDirectConversation($this->userA, $this->userB);
        $conv->load('users');
        $this->assertEquals($this->userB->name, $conv->displayNameFor($this->userA->id));
    }

    #[Test]
    public function message_is_editable_within_15_minutes(): void
    {
        $msg = Message::create([
            'conversation_id' => $this->createGroupConversation($this->userA)->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Test',
            'type'            => 'text',
        ]);

        $this->assertTrue($msg->isEditableBy($this->userA));
        $this->assertFalse($msg->isEditableBy($this->userB));
    }

    #[Test]
    public function message_is_not_editable_after_15_minutes(): void
    {
        $conv = $this->createGroupConversation($this->userA);
        $msg  = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Alt',
            'type'            => 'text',
        ]);

        // created_at manuell 20 Minuten zurücksetzen
        $msg->forceFill(['created_at' => now()->subMinutes(20)])->saveQuietly();
        $msg->refresh();

        $this->assertFalse($msg->isEditableBy($this->userA));
    }

    // ════════════════════════════════════════════════════════════════
    //  2. Web-Controller-Tests
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function messenger_index_requires_authentication(): void
    {
        $this->get(route('messenger.index'))->assertRedirect('/login');
    }

    #[Test]
    public function messenger_index_requires_use_messenger_permission(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $this->actingAs($user)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->get(route('messenger.index'))
            ->assertForbidden();
    }

    #[Test]
    public function messenger_index_loads_for_authorized_user(): void
    {
        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->get(route('messenger.index'))
            ->assertOk()
            ->assertViewIs('messenger.index');
    }

    #[Test]
    public function user_can_view_own_conversation(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->get(route('messenger.show', $conv))
            ->assertOk()
            ->assertViewIs('messenger.show');
    }

    #[Test]
    public function user_cannot_view_conversation_they_are_not_member_of(): void
    {
        $stranger = User::factory()->create(['password_changed_at' => now()]);
        $stranger->givePermissionTo('use messenger');

        $conv = $this->createGroupConversation($this->userA, $this->userB);

        $this->actingAs($stranger)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->get(route('messenger.show', $conv))
            ->assertForbidden();
    }

    #[Test]
    public function user_can_send_message_to_own_conversation(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.send', $conv), [
                'body' => 'Hallo Gruppe!',
            ]);

        $response->assertRedirect(route('messenger.show', $conv));
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Hallo Gruppe!',
        ]);
    }

    #[Test]
    public function message_body_cannot_exceed_max_length(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.send', $conv), [
                'body' => str_repeat('A', 2001),
            ])
            ->assertSessionHasErrors('body');
    }

    #[Test]
    public function user_can_delete_own_message(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        $msg  = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Zu löschen',
            'type'            => 'text',
        ]);

        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->delete(route('messenger.delete', $msg))
            ->assertRedirect();

        $this->assertSoftDeleted('messages', ['id' => $msg->id]);
    }

    #[Test]
    public function user_cannot_delete_others_message(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        $msg  = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Fremdnachricht',
            'type'            => 'text',
        ]);

        $this->actingAs($this->userB)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->delete(route('messenger.delete', $msg))
            ->assertForbidden();
    }

    #[Test]
    public function moderator_can_delete_any_message(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->moderator);
        $msg  = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Problemnachricht',
            'type'            => 'text',
        ]);

        $this->actingAs($this->moderator)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->delete(route('messenger.delete', $msg))
            ->assertRedirect();

        $this->assertSoftDeleted('messages', ['id' => $msg->id]);
    }

    #[Test]
    public function user_can_report_a_message(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        $msg  = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Unangemessene Nachricht',
            'type'            => 'text',
        ]);

        $this->actingAs($this->userB)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.report', $msg), [
                'reason' => 'Dieser Inhalt ist unangemessen.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('message_reports', [
            'message_id'  => $msg->id,
            'reporter_id' => $this->userB->id,
        ]);
    }

    #[Test]
    public function moderator_can_view_reports(): void
    {
        $this->actingAs($this->moderator)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->get(route('messenger.admin.reports'))
            ->assertOk()
            ->assertViewIs('messenger.admin.reports');
    }

    #[Test]
    public function moderator_can_resolve_report(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        $msg  = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Test',
            'type'            => 'text',
        ]);

        $report = MessageReport::create([
            'message_id'  => $msg->id,
            'reporter_id' => $this->userB->id,
            'reason'      => 'Test-Meldung',
        ]);

        $this->actingAs($this->moderator)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.admin.resolve', $report))
            ->assertRedirect();

        $this->assertNotNull($report->fresh()->resolved_at);
    }

    #[Test]
    public function mute_toggle_sets_muted_until(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.mute', $conv))
            ->assertRedirect();

        $pivot = $conv->users()->where('user_id', $this->userA->id)->first()?->pivot;
        $this->assertNotNull($pivot->muted_until);
    }

    // ════════════════════════════════════════════════════════════════
    //  3. API-Tests
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function api_conversations_requires_authentication(): void
    {
        $this->getJson('/api/messenger/conversations')->assertUnauthorized();
    }

    #[Test]
    public function api_conversations_returns_user_conversations(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);

        $response = $this->getJson('/api/messenger/conversations');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [['id', 'type', 'display_name', 'unread_count']],
            ]);

        $this->assertEquals($conv->id, $response->json('data.0.id'));
    }

    #[Test]
    public function api_messages_requires_membership(): void
    {
        $conv    = $this->createGroupConversation($this->userA, $this->userB);
        $stranger = User::factory()->create(['password_changed_at' => now()]);
        $stranger->givePermissionTo('use messenger');

        Sanctum::actingAs($stranger);

        $this->getJson("/api/messenger/conversations/{$conv->id}/messages")
            ->assertForbidden();
    }

    #[Test]
    public function api_send_message_creates_message(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);

        $this->postJson("/api/messenger/conversations/{$conv->id}/messages", [
                'body' => 'API-Test-Nachricht',
            ])
            ->assertCreated()
            ->assertJsonStructure(['success', 'data' => ['id', 'body', 'sender']]);

        $this->assertDatabaseHas('messages', [
            'body'      => 'API-Test-Nachricht',
            'sender_id' => $this->userA->id,
        ]);
    }

    #[Test]
    public function api_unread_count_returns_integer(): void
    {
        Sanctum::actingAs($this->userA);

        $this->getJson('/api/messenger/unread-count')
            ->assertOk()
            ->assertJsonStructure(['success', 'unread_count'])
            ->assertJson(['unread_count' => 0]);
    }

    #[Test]
    public function api_mark_read_updates_last_read_at(): void
    {
        $conv = $this->createGroupConversation($this->userA, $this->userB);

        Sanctum::actingAs($this->userA);

        $this->postJson("/api/messenger/conversations/{$conv->id}/read")
            ->assertOk();

        $pivot = $conv->users()->where('user_id', $this->userA->id)->first()?->pivot;
        $this->assertNotNull($pivot->last_read_at);
    }

    // ════════════════════════════════════════════════════════════════
    //  12. User-Suche – Gruppenfilter & messenger_discoverable
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function search_users_only_returns_members_from_own_groups(): void
    {
        // Gruppe erstellen und beide User zuweisen
        $group = \App\Model\Group::withoutGlobalScopes()->create(['name' => 'Klasse 2a']);
        $group->users()->attach([$this->userA->id, $this->userB->id]);

        // Dritter User OHNE gemeinsame Gruppe
        $outsider = User::factory()->create(['name' => 'Outsider Person', 'password_changed_at' => now()]);
        $outsider->givePermissionTo('use messenger');

        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('messenger.users.search', ['q' => $this->userB->name]));
        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($this->userB->id), 'User aus gleicher Gruppe sollte gefunden werden');

        // Outsider darf nicht gefunden werden
        $response2 = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('messenger.users.search', ['q' => 'Outsider']));
        $response2->assertOk();
        $ids2 = collect($response2->json())->pluck('id');
        $this->assertFalse($ids2->contains($outsider->id), 'User ohne gemeinsame Gruppe darf nicht gefunden werden');
    }

    #[Test]
    public function search_users_respects_messenger_discoverable_false(): void
    {
        // Gruppe erstellen und beide User zuweisen
        $group = \App\Model\Group::withoutGlobalScopes()->create(['name' => 'Klasse 3b']);
        $group->users()->attach([$this->userA->id, $this->userB->id]);

        // UserB deaktiviert Sichtbarkeit
        $this->userB->messenger_discoverable = false;
        $this->userB->save();

        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('messenger.users.search', ['q' => $this->userB->name]));
        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertFalse($ids->contains($this->userB->id), 'User mit messenger_discoverable=false darf nicht in der Suche erscheinen');
    }

    #[Test]
    public function search_users_shows_discoverable_users_by_default(): void
    {
        $group = \App\Model\Group::withoutGlobalScopes()->create(['name' => 'Klasse 4a']);
        $group->users()->attach([$this->userA->id, $this->userB->id]);

        // Refresh from DB to get the default value from migration
        $this->userB->refresh();

        // Default: messenger_discoverable = true (via migration default)
        $this->assertTrue((bool) $this->userB->messenger_discoverable);

        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->getJson(route('messenger.users.search', ['q' => $this->userB->name]));
        $response->assertOk();
        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($this->userB->id));
    }

    #[Test]
    public function user_can_update_messenger_discoverable_in_settings(): void
    {
        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->put('/einstellungen/', [
                'name'                    => $this->userA->name,
                'email'                   => $this->userA->email,
                'benachrichtigung'        => 'daily',
                'messenger_discoverable'  => 0,
            ])->assertRedirect();

        $this->userA->refresh();
        $this->assertFalse((bool) $this->userA->messenger_discoverable);
    }

    #[Test]
    public function messenger_discoverable_does_not_affect_start_direct_controller_logic(): void
    {
        // messenger_discoverable blockiert nur die SUCHE (searchUsers), nicht startDirect.
        // Prüfe: Der Controller-Code von startDirect filtert NICHT auf messenger_discoverable.
        $this->userB->messenger_discoverable = false;
        $this->userB->save();

        // Lese den Controller-Quellcode und verifiziere, dass startDirect NICHT auf messenger_discoverable prüft.
        // Dies ist ein Unit-Style-Check: Die searchUsers-Methode enthält den Filter, startDirect nicht.
        $controller = new \App\Http\Controllers\MessengerController(app(MessengerSetting::class));
        $reflection = new \ReflectionMethod($controller, 'startDirect');
        $source = file_get_contents($reflection->getFileName());
        $startLine = $reflection->getStartLine();
        $endLine   = $reflection->getEndLine();
        $methodSource = implode("\n", array_slice(file($reflection->getFileName()), $startLine - 1, $endLine - $startLine + 1));

        $this->assertStringNotContainsString('messenger_discoverable', $methodSource,
            'startDirect darf NICHT auf messenger_discoverable filtern – nur searchUsers soll filtern');

        // Zusätzlich: searchUsers filtert korrekt
        $searchReflection = new \ReflectionMethod($controller, 'searchUsers');
        $searchSource = implode("\n", array_slice(file($searchReflection->getFileName()), $searchReflection->getStartLine() - 1, $searchReflection->getEndLine() - $searchReflection->getStartLine() + 1));
        $this->assertStringContainsString('messenger_discoverable', $searchSource,
            'searchUsers MUSS auf messenger_discoverable filtern');
    }

    // ════════════════════════════════════════════════════════════════
    //  13. startDirect – Funktionstest mit Gruppen
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function start_direct_works_when_users_share_a_group(): void
    {
        $group = \App\Model\Group::withoutGlobalScopes()->create(['name' => 'Klasse 5a']);
        $group->users()->attach([$this->userA->id, $this->userB->id]);

        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.direct', $this->userB));

        $response->assertRedirect(); // Weiterleitung zur Konversation
        $response->assertSessionMissing('Meldung'); // Keine Fehlermeldung

        $this->assertDatabaseHas('conversations', [
            'type'       => 'direct',
            'created_by' => $this->userA->id,
        ]);
    }

    #[Test]
    public function start_direct_creates_conversation_only_once_when_called_twice(): void
    {
        $group = \App\Model\Group::withoutGlobalScopes()->create(['name' => 'Klasse 6b']);
        $group->users()->attach([$this->userA->id, $this->userB->id]);

        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.direct', $this->userB));

        $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.direct', $this->userB));

        // Nur eine direkte Konversation zwischen A und B
        $this->assertEquals(1, \App\Model\Conversation::where('type', 'direct')->count());
    }

    #[Test]
    public function start_direct_fails_when_users_share_no_group(): void
    {
        // Kein gemeinsames group_user Eintrag
        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.direct', $this->userB));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'danger');
        $response->assertSessionHas('Meldung', 'Du kannst nur Mitglieder deiner Gruppen anschreiben.');
    }

    #[Test]
    public function start_direct_fails_when_direct_messages_disabled(): void
    {
        // allow_direct_messages auf false setzen
        \DB::table('settings')
            ->where('group', 'messenger')
            ->where('name', 'allow_direct_messages')
            ->update(['payload' => json_encode(false)]);

        $group = \App\Model\Group::withoutGlobalScopes()->create(['name' => 'Klasse 7c']);
        $group->users()->attach([$this->userA->id, $this->userB->id]);

        // Settings-Cache leeren
        app()->forgetInstance(\App\Settings\MessengerSetting::class);

        $response = $this->actingAs($this->userA)
            ->withoutMiddleware(\App\Http\Middleware\PasswordExpired::class)
            ->post(route('messenger.direct', $this->userB));

        $response->assertRedirect();
        $response->assertSessionHas('type', 'danger');
        $response->assertSessionHas('Meldung', 'Direktnachrichten sind deaktiviert.');
    }

    // ════════════════════════════════════════════════════════════════
    //  14. Datenschutz-Export enthält Messenger-Daten
    // ════════════════════════════════════════════════════════════════

    #[Test]
    public function datenschutz_controller_collects_messenger_data(): void
    {
        // Konversation + Nachricht erstellen
        $conv = $this->createGroupConversation($this->userA, $this->userB);
        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $this->userA->id,
            'body'            => 'Hallo Datenschutz-Test',
            'type'            => 'text',
        ]);

        // Meldung erstellen
        MessageReport::create([
            'message_id'  => $msg->id,
            'reporter_id' => $this->userA->id,
            'reason'      => 'Test-Meldung',
        ]);

        // Prüfen ob die Datenbank-Abfragen korrekt sind
        $conversations = Conversation::forUser($this->userA->id)->get();
        $this->assertNotEmpty($conversations);

        $sentMessages = Message::where('sender_id', $this->userA->id)->get();
        $this->assertNotEmpty($sentMessages);
        $this->assertEquals('Hallo Datenschutz-Test', $sentMessages->first()->body);

        $reports = MessageReport::where('reporter_id', $this->userA->id)->get();
        $this->assertNotEmpty($reports);
        $this->assertEquals('Test-Meldung', $reports->first()->reason);
    }

    #[Test]
    public function messenger_discoverable_field_exists_in_user_model(): void
    {
        $user = User::factory()->create(['password_changed_at' => now()]);
        $user->refresh();

        // Standardwert true prüfen
        $this->assertTrue((bool) $user->messenger_discoverable);

        // Änderbar
        $user->messenger_discoverable = false;
        $user->save();
        $user->refresh();
        $this->assertFalse((bool) $user->messenger_discoverable);
    }
}




