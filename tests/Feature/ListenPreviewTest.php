<?php

namespace Tests\Feature;

use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Model\User;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * Vorschau: Listen-Ersteller und Verwaltung sehen eine Liste so, wie Eltern sie
 * nach der Veröffentlichung sehen – ohne Verwaltungsfunktionen.
 */
class ListenPreviewTest extends TestCase
{
    use BuildsFamilies;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        $this->owner = $this->makeParent(['changePassword' => false]);
    }

    private function liste(string $type, array $attributes = []): Liste
    {
        return Liste::create(array_merge([
            'listenname' => 'Sommerfest',
            'type' => $type,
            'besitzer' => $this->owner->id,
            'visible_for_all' => false,
            'active' => false,
            'ende' => now()->addWeek(),
            'multiple' => true,
            'make_new_entry' => false,
            'duration' => 30,
        ], $attributes));
    }

    #[Test]
    public function owner_sees_eintrag_liste_as_parents_would(): void
    {
        $liste = $this->liste('eintrag');
        $frei = Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Kuchen', 'created_by' => $this->owner->id]);
        $vergeben = $this->makeParent(['name' => 'Erika Muster']);
        Listen_Eintragungen::create(['listen_id' => $liste->id, 'eintragung' => 'Salat', 'created_by' => $this->owner->id, 'user_id' => $vergeben->id]);

        $this->actingAs($this->owner)->get(route('listen.preview', $liste->id))
            ->assertOk()
            ->assertSee('Vorschau beenden')
            ->assertSee('So sehen Eltern diese Liste, sobald sie veröffentlicht ist.')
            ->assertDontSee('@if', false)
            ->assertSee('Kuchen')
            ->assertSee('Reservieren')
            ->assertSee('vergeben')
            ->assertDontSee('Erika Muster')
            ->assertDontSee(route('listen.eintragungen.copy', $frei->id), false)
            ->assertDontSee('onclick="generatePDF()"', false)
            ->assertDontSee('Eintrag hinzufügen');
    }

    #[Test]
    public function preview_shows_new_entry_form_only_when_parents_may_add_entries(): void
    {
        $liste = $this->liste('eintrag', ['make_new_entry' => true]);

        $this->actingAs($this->owner)->get(route('listen.preview', $liste->id))
            ->assertOk()
            ->assertSee('Eintrag hinzufügen');
    }

    #[Test]
    public function owner_sees_termin_liste_without_management_actions(): void
    {
        $liste = $this->liste('termin');
        listen_termine::create(['listen_id' => $liste->id, 'termin' => now()->addDays(2), 'duration' => 30]);

        $this->actingAs($this->owner)->get(route('listen.preview', $liste->id))
            ->assertOk()
            ->assertSee('Vorschau beenden')
            ->assertSee('Reservieren')
            ->assertDontSee('Termin löschen')
            ->assertDontSee('data-target="#createEintragungModal"', false);
    }

    #[Test]
    public function other_users_cannot_open_preview(): void
    {
        $liste = $this->liste('eintrag');

        $this->actingAs($this->makeParent(['changePassword' => false]))
            ->get(route('listen.preview', $liste->id))
            ->assertForbidden();
    }
}
