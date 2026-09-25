<?php

namespace Tests\Feature\Familienmodell;

use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\BuildsFamilies;
use Tests\TestCase;

/**
 * FAM-15: Bereitschaftsprüfung vor dem Umschalten.
 */
class FamilyStatusCommandTest extends TestCase
{
    use BuildsFamilies;

    #[Test]
    public function reports_blockers_until_migration_is_done(): void
    {
        $this->coupleWithChildOfA();

        $this->artisan('family:status')
            ->expectsOutputToContain('family:migrate-from-sorg2')
            ->assertFailed();

        $this->artisan('family:migrate-from-sorg2', ['--report' => storage_path('framework/testing/status.csv')])->assertSuccessful();
        @unlink(storage_path('framework/testing/status.csv'));

        $this->artisan('family:status')
            ->expectsOutputToContain('Bereit zum Umschalten')
            ->assertSuccessful();
    }
}
