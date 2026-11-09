<?php

namespace Tests\Feature\Schuelerkonten;

use App\Model\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Der Schüler-Scope merkt sich, dass `users.account_type` existiert. Wird im selben Prozess
 * migriert (z. B. `demo:setup --existing=neu` → `migrate:fresh`), laufen ältere Migrationen,
 * die das User-Model nutzen, wieder ohne die Spalte – der Scope darf sie dann nicht verlangen.
 */
class StudentScopeMigrationTest extends TestCase
{
    /** @test */
    public function scope_forgets_the_column_when_migrations_run(): void
    {
        User::factory()->create();
        $this->assertSame(1, User::count()); // Scope hat die Spalte gesehen und gemerkt

        Artisan::call('migrate:rollback', [
            '--path' => 'database/migrations/2026_11_04_000001_add_student_account_columns.php',
            '--force' => true,
        ]);
        $this->assertFalse(Schema::hasColumn('users', 'account_type'));

        $this->assertSame(1, User::count());
    }
}
