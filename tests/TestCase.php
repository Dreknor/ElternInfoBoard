<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use JMac\Testing\Traits\AdditionalAssertions;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use AdditionalAssertions, CreatesApplication, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Spatie Permission-Cache vor jedem Test leeren, um PermissionAlreadyExists /
        // PermissionDoesNotExist-Fehler durch In-Memory-Cache-Kontamination zu vermeiden.
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
