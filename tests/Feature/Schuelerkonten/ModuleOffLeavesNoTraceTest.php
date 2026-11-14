<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\User;
use App\Support\StudentAccounts;
use Spatie\Permission\Models\Permission;
use Tests\Concerns\ActivatesModules;
use Tests\TestCase;

/**
 * Bei ausgeschaltetem Modul weist nichts in der Oberfläche auf Schülerkonten hin (§1.1).
 */
class ModuleOffLeavesNoTraceTest extends TestCase
{
    use ActivatesModules;

    private const PAGES = ['/home', '/einstellungen', '/users', '/termine/create', '/roles'];

    private const MARKERS = ['Schülerkonto', 'Schülerkonten', 'schuelerkonten', 'Sichtbar für', 'schueler/anmelden'];

    private function admin(): User
    {
        foreach (['edit user', 'edit posts', 'create posts', 'edit termin', 'edit settings', 'view logs', 'edit permission', 'import user', 'view all', StudentAccounts::PERMISSION_CREATE_POSTS] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $admin = User::factory()->create(['changePassword' => false]);
        $admin->givePermissionTo(Permission::all());

        return $admin;
    }

    private function pagesContain(User $admin): array
    {
        $found = [];
        foreach (self::PAGES as $uri) {
            $response = $this->actingAs($admin)->get($uri);
            if ($response->status() !== 200) {
                continue;
            }
            foreach (self::MARKERS as $marker) {
                if (str_contains((string) $response->getContent(), $marker)) {
                    $found[] = "{$uri}: {$marker}";
                }
            }
        }

        return $found;
    }

    public function test_no_trace_while_module_is_off(): void
    {
        $admin = $this->admin();
        $this->assertSame([], $this->pagesContain($admin));
        $this->get('/login')->assertDontSee('Schüler-Login')->assertDontSee('schueler/anmelden');
    }

    public function test_entry_points_appear_once_enabled(): void
    {
        $admin = $this->admin();
        config(['modules.unlocked' => [StudentAccounts::MODULE]]);
        $this->activateModule(StudentAccounts::MODULE);

        $this->assertNotSame([], $this->pagesContain($admin->fresh()));
    }
}
