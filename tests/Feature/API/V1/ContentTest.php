<?php

namespace Tests\Feature\API\V1;

use App\Model\AbfrageOptions;
use App\Model\Liste;
use App\Model\listen_termine;
use App\Model\Poll;
use App\Model\Rueckmeldungen;
use App\Model\UserRueckmeldungen;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

class ContentTest extends AppApiTestCase
{
    private function rueckmeldung(int $postId, array $attributes = []): Rueckmeldungen
    {
        return Rueckmeldungen::withoutEvents(fn () => Rueckmeldungen::create($attributes + [
            'post_id' => $postId,
            'type' => 'email',
            'empfaenger' => 'schule@example.org',
            'ende' => now()->addDays(3),
            'text' => 'Bitte antworten',
            'pflicht' => true,
            'commentable' => false,
            'multiple' => false,
        ]));
    }

    /** @test */
    public function posts_list_is_paginated_and_only_shows_own_groups(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $visible = $this->postIn($group, ['read_receipt' => true]);
        $this->postIn($this->group('Andere'));
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/posts')->assertOk();
        $this->assertSame([$visible->id], array_column($response->json('data'), 'id'));
        $response->assertJsonPath('data.0.todo', 'read_receipt')
            ->assertJsonStructure(['meta' => ['next_cursor']]);

        $this->postJson("/api/v1/posts/{$visible->id}/read")->assertOk();
        $this->getJson('/api/v1/posts')->assertJsonPath('data.0.todo', null)->assertJsonPath('data.0.read_receipt.confirmed', true);
    }

    /** @test */
    public function post_detail_contains_sanitized_html(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $post = $this->postIn($group, ['news' => '<p>Hallo <strong>Eltern</strong></p><script>alert(1)</script>']);
        Sanctum::actingAs($user);

        $html = $this->getJson("/api/v1/posts/{$post->id}")->assertOk()->json('data.news_html');
        $this->assertStringContainsString('<strong>Eltern</strong>', $html);
        $this->assertStringNotContainsString('script', $html);
    }

    /** @test */
    public function text_feedback_is_stored_once_per_family_and_editable(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $post = $this->postIn($group);
        $this->rueckmeldung($post->id);
        Sanctum::actingAs($user);

        $id = $this->postJson("/api/v1/posts/{$post->id}/feedback", ['text' => 'Wir kommen'])->assertCreated()->json('data.id');
        $this->postJson("/api/v1/posts/{$post->id}/feedback", ['text' => 'Nochmal'])->assertStatus(409);
        $this->putJson("/api/v1/feedback/{$id}", ['text' => 'Wir kommen zu zweit'])->assertOk();
        $this->assertSame('Wir kommen zu zweit', UserRueckmeldungen::find($id)->text);
    }

    /** @test */
    public function abfrage_validates_options_required_fields_and_deadline(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $post = $this->postIn($group);
        $r = $this->rueckmeldung($post->id, ['type' => 'abfrage', 'max_answers' => 1]);
        $a = AbfrageOptions::create(['rueckmeldung_id' => $r->id, 'type' => 'check', 'option' => 'Ja', 'required' => false]);
        $b = AbfrageOptions::create(['rueckmeldung_id' => $r->id, 'type' => 'check', 'option' => 'Nein', 'required' => false]);
        $text = AbfrageOptions::create(['rueckmeldung_id' => $r->id, 'type' => 'text', 'option' => 'Name', 'required' => true]);
        $otherPost = $this->postIn($group);
        $other = $this->rueckmeldung($otherPost->id, ['type' => 'abfrage']);
        $foreign = AbfrageOptions::create(['rueckmeldung_id' => $other->id, 'type' => 'check', 'option' => 'X', 'required' => false]);
        Sanctum::actingAs($user);

        $url = "/api/v1/posts/{$post->id}/abfrage";
        $this->postJson($url, ['answers' => [$foreign->id => '1', $text->id => 'Mia']])->assertStatus(422);
        $this->postJson($url, ['answers' => [$a->id => '1']])->assertStatus(422); // Pflichtfeld fehlt
        $this->postJson($url, ['answers' => [$a->id => '1', $b->id => '1', $text->id => 'Mia']])->assertStatus(422); // max 1
        $this->postJson($url, ['answers' => [$a->id => '1', $text->id => 'Mia']])->assertCreated();
        // Erneut senden ersetzt die Antwort (nicht mehrfach)
        $this->postJson($url, ['answers' => [$b->id => '1', $text->id => 'Mia']])->assertCreated();
        $this->assertSame(1, UserRueckmeldungen::count());
        $this->assertSame([$b->id, $text->id], DB::table('abfrage_answers')->whereNull('deleted_at')->orderBy('option_id')->pluck('option_id')->map(fn ($v) => (int) $v)->all());

        $r->update(['ende' => now()->subDays(2)]);
        $this->postJson($url, ['answers' => [$a->id => '1', $text->id => 'Mia']])->assertStatus(410);
    }

    /** @test */
    public function poll_vote_checks_end_date_and_only_once(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $post = $this->postIn($group);
        $poll = Poll::create(['poll_name' => 'Ausflug?', 'post_id' => $post->id, 'author_id' => $post->author, 'max_number' => 1, 'ends' => now()->addDay()]);
        $option = $poll->options()->create(['option' => 'Zoo']);
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/posts/{$post->id}/poll/vote", ['option_ids' => [$option->id]])->assertCreated()
            ->assertJsonPath('data.has_voted', true)->assertJsonPath('data.options.0.votes', 1);
        $this->postJson("/api/v1/posts/{$post->id}/poll/vote", ['option_ids' => [$option->id]])->assertStatus(409);

        $poll->update(['ends' => now()->subDays(2)]);
        $this->postJson("/api/posts/{$post->id}/poll/vote", ['option_ids' => [$option->id]])->assertStatus(410);
    }

    /** @test */
    public function list_slot_cannot_be_double_booked_and_family_limit_applies(): void
    {
        $group = $this->group();
        $a = $this->parentIn($group);
        $b = $this->parentIn($group);
        $liste = Liste::create(['listenname' => 'Elterngespräche', 'type' => 'termin', 'besitzer' => $a->id, 'visible_for_all' => false,
            'active' => true, 'ende' => now()->addWeek(), 'multiple' => false, 'duration' => 15]);
        DB::table('group_listen')->insert(['group_id' => $group->id, 'liste_id' => $liste->id]);
        $slot1 = listen_termine::create(['listen_id' => $liste->id, 'termin' => now()->addDays(2)]);
        $slot2 = listen_termine::create(['listen_id' => $liste->id, 'termin' => now()->addDays(3)]);

        Sanctum::actingAs($a);
        $this->postJson("/api/v1/listen/termine/{$slot1->id}/reservation")->assertCreated();
        $this->postJson("/api/v1/listen/termine/{$slot2->id}/reservation")->assertStatus(409); // nur ein Termin je Familie
        $this->getJson("/api/v1/listen/{$liste->id}")->assertOk()->assertJsonPath('data.termine.0.status', 'mine');

        Sanctum::actingAs($b);
        $this->postJson("/api/v1/listen/termine/{$slot1->id}/reservation")->assertStatus(409);
        $this->putJson("/api/listen/termin/{$slot1->id}/reservieren")->assertStatus(409);
        $this->getJson('/api/v1/listen')->assertOk()->assertJsonPath('data.0.free_count', 1);
    }

    /** @test */
    public function dashboard_and_bootstrap_return_everything_in_one_request(): void
    {
        $group = $this->group();
        $user = $this->parentIn($group);
        $this->childOf($user, $group);
        $this->postIn($group, ['read_receipt' => true]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/parent/dashboard')->assertOk()
            ->assertJsonCount(1, 'data.todo')
            ->assertJsonCount(1, 'data.children')
            ->assertJsonStructure(['data' => ['todo', 'children', 'termine', 'posts', 'losung', 'active_diseases']]);

        $this->getJson('/api/v1/bootstrap')->assertOk()
            ->assertJsonPath('data.counters.todo', 1)
            ->assertJsonStructure(['data' => ['user', 'permissions', 'modules', 'children' => [['my_rights']], 'theme' => ['colors'], 'logo_url']]);
    }
}
