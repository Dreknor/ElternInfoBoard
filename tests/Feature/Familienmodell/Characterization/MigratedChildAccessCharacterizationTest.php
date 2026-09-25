<?php

namespace Tests\Feature\Familienmodell\Characterization;

use App\Services\Family\FamilyResolver;
use App\Services\Family\Sorg2MigrationService;

/**
 * Dieselben Erwartungen wie im Legacy-Modell – aber nach der Datenmigration
 * (FAM-09) im kind-zentrierten Modus. Belegt: Nach Migration + Umschalten
 * verliert niemand den Zugriff, Fremde erhalten keinen.
 */
class MigratedChildAccessCharacterizationTest extends ChildAccessCharacterizationTest
{
    protected function prepareFamilies(): void
    {
        app(Sorg2MigrationService::class)->run();
        $this->useResolver(FamilyResolver::MODE_CHILD_CENTRIC);
    }
}
