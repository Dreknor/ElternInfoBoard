<?php

namespace Tests\Feature\Familienmodell;

use App\Model\Child;
use App\Model\Group;
use App\Model\User;
use App\Settings\CareSetting;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Eltern-Tab der Anwesenheit: Kontakte mit Telefonnummern in beiden Darstellungen,
 * im Modus legacy inklusive sorg2-Partner.
 */
class AnwesenheitElternKontakteTest extends TestCase
{
    public static function darstellungen(): array
    {
        return ['einfache Liste' => [false], 'Detailansicht' => [true]];
    }

    #[Test]
    #[DataProvider('darstellungen')]
    public function parent_contacts_contain_phone_numbers(bool $detailed): void
    {
        config(['family.resolver' => 'legacy']);

        $group = Group::factory()->create();
        $class = Group::factory()->create();
        $settings = new CareSetting;
        $settings->groups_list = [$group->id];
        $settings->class_list = [$class->id];
        $settings->view_detailed_care = $detailed;
        $settings->hide_childs_when_absent = false;
        $settings->show_parents = true;
        $settings->save();

        Permission::findOrCreate('edit schickzeiten', 'web');
        $staff = User::factory()->create();
        $staff->givePermissionTo('edit schickzeiten');

        $child = Child::factory()->create(['group_id' => $group->id, 'class_id' => $class->id]);
        $parent = User::factory()->create(['phone' => '0351 111', 'publicPhone' => '0170 222']);
        $partner = User::factory()->create(['publicPhone' => '0171 333', 'sorg2' => $parent->id]);
        $parent->update(['sorg2' => $partner->id]);
        $parent->children_rel()->attach($child);

        $html = $this->actingAs($staff)->get(route('anwesenheit.index'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression("/data-child='([^']*)'/", $html);
        preg_match("/data-child='([^']*)'/", $html, $match);
        $contacts = collect(json_decode(html_entity_decode($match[1], ENT_QUOTES), true)['parents'])->keyBy('email');

        $this->assertCount(2, $contacts);
        $this->assertSame('0351 111', $contacts[$parent->email]['phone']);
        $this->assertSame('0170 222', $contacts[$parent->email]['publicPhone']);
        $this->assertSame('0171 333', $contacts[$partner->email]['publicPhone']);
    }
}
