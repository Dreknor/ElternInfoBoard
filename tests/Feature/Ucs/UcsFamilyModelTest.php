<?php

namespace Tests\Feature\Ucs;

use App\Model\Child;
use App\Model\Conversation;
use App\Model\Family;
use App\Model\Group;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use App\Services\Ucs\Dto\KelvinStudentDto;
use App\Services\Ucs\Dto\KelvinUserDto;
use App\Services\Ucs\KelvinClient;
use App\Services\Ucs\UcsSyncService;
use App\Settings\UcsSetting;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * FAM-13: UCS-Sync im kind-zentrierten Familienmodell.
 */
class UcsFamilyModelTest extends TestCase
{
    private const SCHOOL = 'GS-XY';

    protected function setUp(): void
    {
        parent::setUp();
        config(['family.resolver' => FamilyResolver::MODE_CHILD_CENTRIC]);
    }

    private function service(array $parents, array $students): UcsSyncService
    {
        $settings = $this->createStub(UcsSetting::class);
        $settings->method('save')->willReturnSelf();
        foreach (['enabled' => true, 'school' => self::SCHOOL, 'on_login_timeout' => 5] as $prop => $value) {
            $settings->{$prop} = $value;
        }

        $client = $this->createMock(KelvinClient::class);
        $client->method('listStudents')->willReturnCallback(fn () => (fn () => yield from $students)());
        $client->method('listParents')->willReturnCallback(fn () => (fn () => yield from $parents)());

        return new UcsSyncService($client, $settings);
    }

    private function parent(string $username, array $wards): KelvinUserDto
    {
        return KelvinUserDto::fromArray([
            'username' => $username, 'record_uid' => 'uid-'.$username, 'firstname' => 'Test', 'lastname' => 'Elter',
            'email' => $username.'@example.de', 'school' => self::SCHOOL, 'roles' => ['legal_guardian'],
            'legal_wards' => array_map(fn ($w) => "https://ucs.example.de/ucsschool/kelvin/v1/users/{$w}", $wards),
            'url' => "https://ucs.example.de/ucsschool/kelvin/v1/users/{$username}",
        ]);
    }

    private function student(string $username, array $classes = ['1a']): KelvinStudentDto
    {
        return KelvinStudentDto::fromArray([
            'username' => $username, 'record_uid' => 'uid-'.$username, 'firstname' => 'Kind', 'lastname' => 'Muster',
            'school' => self::SCHOOL, 'roles' => ['student'], 'school_classes' => [self::SCHOOL => $classes],
            'url' => "https://ucs.example.de/ucsschool/kelvin/v1/users/{$username}",
        ]);
    }

    #[Test]
    public function parents_with_same_wards_form_one_family(): void
    {
        $counts = $this->service(
            [$this->parent('anna', ['max', 'mia']), $this->parent('bert', ['max', 'mia'])],
            [$this->student('max'), $this->student('mia')],
        )->run();

        $anna = User::where('ucs_username', 'anna')->first();
        $bert = User::where('ucs_username', 'bert')->first();
        $this->assertNotNull($anna->family_id);
        $this->assertSame($anna->family_id, $bert->family_id);
        $this->assertSame(Family::SOURCE_UCS, $anna->family->source);
        $this->assertSame(1, $counts['families_created']);

        $max = Child::where('ucs_username', 'max')->first();
        $this->assertSame('ucs', $max->parents()->where('users.id', $anna->id)->first()->pivot->source);
    }

    #[Test]
    public function locked_family_is_not_changed_by_sync(): void
    {
        $locked = Family::factory()->locked()->create();
        $anna = User::factory()->create(['ucs_username' => 'anna', 'ucs_uuid' => 'uid-anna', 'email' => 'anna@example.de', 'family_id' => $locked->id]);

        $this->service([$this->parent('anna', ['max']), $this->parent('bert', ['max'])], [$this->student('max')])->run();

        $this->assertSame($locked->id, $anna->fresh()->family_id);
        $bert = User::where('ucs_username', 'bert')->first();
        $this->assertNotSame($locked->id, $bert->family_id);
    }

    #[Test]
    public function combined_classes_are_all_derived_groups(): void
    {
        $this->service([$this->parent('anna', ['max'])], [$this->student('max', ['1a', '2b'])])->run();

        $anna = User::where('ucs_username', 'anna')->first();
        $max = Child::where('ucs_username', 'max')->first();
        $classGroups = Group::withoutGlobalScopes()->whereIn('name', ['1a', '2b'])->pluck('id')->sort()->values()->all();

        $this->assertCount(2, $classGroups);
        $this->assertSame($classGroups, DB::table('child_group')->where('child_id', $max->id)->pluck('group_id')->sort()->values()->all());
        $this->assertSame($classGroups, DB::table('group_user')->where('user_id', $anna->id)->where('is_auto_provisioned', true)->pluck('group_id')->sort()->values()->all());
    }

    #[Test]
    public function removed_ward_drops_ucs_link_and_groups_but_keeps_manual_link(): void
    {
        $this->service([$this->parent('anna', ['max', 'mia'])], [$this->student('max', ['1a']), $this->student('mia', ['2b'])])->run();
        $anna = User::where('ucs_username', 'anna')->first();
        $group2b = Group::withoutGlobalScopes()->where('name', '2b')->first();
        $conversation = Conversation::withoutGlobalScopes()->create([
            'type' => 'group', 'group_id' => $group2b->id, 'is_active' => true, 'title' => '2b', 'created_by' => $anna->id,
        ]);
        $conversation->users()->attach($anna->id, ['joined_at' => now()]);
        $local = Child::factory()->create(['class_id' => Group::factory()->create(['protected' => false])->id, 'group_id' => null]);
        $local->parents()->attach($anna->id); // manuelle Beziehung

        $this->service([$this->parent('anna', ['max'])], [$this->student('max', ['1a']), $this->student('mia', ['2b'])])->run();

        $mia = Child::where('ucs_username', 'mia')->first();
        $this->assertFalse($mia->parents()->where('users.id', $anna->id)->exists());
        $this->assertTrue($local->parents()->where('users.id', $anna->id)->exists());
        $this->assertFalse(DB::table('group_user')->where('user_id', $anna->id)->where('group_id', $group2b->id)->exists());
        $this->assertTrue(DB::table('group_user')->where('user_id', $anna->id)->where('group_id', $local->class_id)->exists());
        $this->assertFalse($conversation->users()->where('users.id', $anna->id)->exists());
    }
}
