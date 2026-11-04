<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\Child;
use App\Model\Conversation;
use App\Model\Group;
use App\Model\User;
use App\Services\Students\StudentAccountService;
use App\Settings\SchuelerSetting;
use App\Support\StudentAccounts;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Chat Schüler ↔ Lehrkräfte (Paket S-14).
 */
class StudentMessengerTest extends TestCase
{
    use ActivatesModules;

    private User $student;

    private User $teacher;

    private User $parent;

    private Group $class;

    private function setUpChat(bool $enabled = true): void
    {
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);
        Permission::findOrCreate('use messenger', 'web');
        Permission::findOrCreate(StudentAccounts::PERMISSION_CREATE_POSTS, 'web');

        $this->class = Group::factory()->create(['name' => '4a']);
        $child = Child::factory()->create(['class_id' => $this->class->id]);
        [$this->student] = app(StudentAccountService::class)->createForChild($child, ['username' => 'kind.chat']);
        $this->student->forceFill(['password' => Hash::make('geheim-12345')])->save();

        $this->teacher = User::factory()->create(['name' => 'Frau Lehrer']);
        $this->teacher->givePermissionTo(['use messenger', StudentAccounts::PERMISSION_CREATE_POSTS]);
        $this->teacher->groups()->attach($this->class->id);

        $this->parent = User::factory()->create(['name' => 'Herr Elternteil']);
        $this->parent->givePermissionTo('use messenger');
        $this->parent->groups()->attach($this->class->id);

        $s = app(SchuelerSetting::class);
        $s->messenger_lehrkraefte = $enabled;
        $s->login_benutzername = true;
        $s->save();
    }

    /** @test */
    public function chat_does_not_exist_while_setting_is_off(): void
    {
        $this->setUpChat(false);
        $this->actingAs($this->student)->get(route('student.home'))->assertOk()->assertDontSee('Chat mit Lehrkräften');
        $this->actingAs($this->student)->get('/schueler/chat')->assertNotFound();
    }

    /** @test */
    public function student_can_only_choose_teachers_of_own_groups(): void
    {
        $this->setUpChat();
        $other = User::factory()->create(['name' => 'Fremde Lehrkraft']);
        $other->givePermissionTo(['use messenger', StudentAccounts::PERMISSION_CREATE_POSTS]);

        $this->actingAs($this->student)->get(route('student.messenger'))
            ->assertOk()->assertSee('Frau Lehrer')->assertDontSee('Herr Elternteil')->assertDontSee('Fremde Lehrkraft');

        $this->post(route('student.messenger.start'), ['teacher_id' => $this->parent->id])->assertSessionHas('type', 'danger');
        $this->post(route('student.messenger.start'), ['teacher_id' => $other->id])->assertSessionHas('type', 'danger');
        $this->assertSame(0, Conversation::count());
    }

    /** @test */
    public function student_and_teacher_exchange_messages_until_the_teacher_closes_the_chat(): void
    {
        $this->setUpChat();
        $this->actingAs($this->student)->post(route('student.messenger.start'), ['teacher_id' => $this->teacher->id])->assertRedirect();
        $conversation = Conversation::firstOrFail();
        $this->assertSame('student', $conversation->type);

        $this->post(route('student.messenger.send', $conversation->id), ['body' => 'Hallo Frau Lehrer'])->assertRedirect();

        // Lehrkraft sieht den Chat im normalen Messenger, mit Namen des Schülers
        $this->actingAs($this->teacher)->get(route('messenger.index'))->assertOk()->assertSee($this->student->name);
        $this->actingAs($this->teacher)->get(route('messenger.show', $conversation))->assertOk()->assertSee('Hallo Frau Lehrer');
        $this->post(route('messenger.send', $conversation), ['body' => 'Hallo zurück'])->assertRedirect();

        $this->actingAs($this->student)->get(route('student.messenger.show', $conversation->id))->assertOk()->assertSee('Hallo zurück');

        $this->actingAs($this->teacher)->post(route('schuelerchat.close', $conversation->id))->assertRedirect();
        $this->actingAs($this->student)->post(route('student.messenger.send', $conversation->id), ['body' => 'Noch was'])->assertSessionHas('type', 'danger');
        $this->post(route('student.messenger.start'), ['teacher_id' => $this->teacher->id])->assertSessionHas('type', 'danger');
    }

    /** @test */
    public function student_chats_vanish_for_teachers_when_switched_off(): void
    {
        $this->setUpChat();
        $this->actingAs($this->student)->post(route('student.messenger.start'), ['teacher_id' => $this->teacher->id]);

        $s = app(SchuelerSetting::class);
        $s->messenger_lehrkraefte = false;
        $s->save();

        $this->actingAs($this->teacher)->get(route('messenger.index'))->assertOk()->assertDontSee('Schülerchat');
    }

    /** @test */
    public function other_students_cannot_read_a_chat(): void
    {
        $this->setUpChat();
        $this->actingAs($this->student)->post(route('student.messenger.start'), ['teacher_id' => $this->teacher->id]);
        $conversation = Conversation::firstOrFail();

        [$other] = app(StudentAccountService::class)->createForChild(Child::factory()->create(['class_id' => $this->class->id]));
        $this->actingAs($other)->get(route('student.messenger.show', $conversation->id))->assertNotFound();
    }

    /** @test */
    public function chat_works_through_the_app_api(): void
    {
        $this->setUpChat();
        $token = $this->postJson('/api/v1/student/token', ['username' => 'kind.chat', 'password' => 'geheim-12345', 'device_name' => 'T'])->json('data.token');
        $auth = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/student/bootstrap', $auth)->assertJsonFragment(['chat']);
        $this->getJson('/api/v1/student/messenger/conversations', $auth)->assertOk()->assertJsonPath('meta.lehrkraefte.0.name', 'Frau Lehrer');
        $r = $this->postJson('/api/v1/student/messenger/conversations', ['teacher_id' => $this->teacher->id], $auth);
        $id = $r->assertCreated()->json('data.id');
        $this->postJson("/api/v1/student/messenger/conversations/{$id}/messages", ['body' => 'Frage zur Hausaufgabe'], $auth)->assertCreated();
        $this->getJson("/api/v1/student/messenger/conversations/{$id}/messages", $auth)->assertOk()->assertJsonPath('data.0.text', 'Frage zur Hausaufgabe');
    }

    /** @test */
    public function deleting_the_account_removes_its_chats(): void
    {
        $this->setUpChat();
        $this->actingAs($this->student)->post(route('student.messenger.start'), ['teacher_id' => $this->teacher->id]);
        app(StudentAccountService::class)->delete($this->student);

        $this->assertSame(0, Conversation::withTrashed()->count());
    }

    /** @test */
    public function student_push_targets_open_the_student_view_in_the_app(): void
    {
        $this->assertSame(['type' => 'student_chat', 'id' => 7], \App\Services\Push\PushTarget::fromUrl('https://schule.de/schueler/chat/7'));
        $this->assertSame(['type' => 'student_post', 'id' => 3], \App\Services\Push\PushTarget::fromUrl('https://schule.de/schueler/nachrichten/3'));
        $this->assertSame(['type' => 'student_termin', 'id' => null], \App\Services\Push\PushTarget::fromUrl('https://schule.de/schueler/termine'));
    }
}
