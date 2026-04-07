<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FirstUserSeeder::class,
            RolesAndPermissionsSeeder::class,
            CreateElternratGroupSeeder::class,
            CreateSettingsSeeder::class,
            AddPermissionScanFilesSeeder::class,
            AddVertretungsplanSettingsSeeder::class,
            UpdateUsersPublicMailPermissionSeeder::class,
            ReactionsSeeder::class,
            UpdatePermissionSeeder::class,
            UpdateModuleSettingsSeeder::class,
            UpdateGroupsPermission::class,
            DiseasesSeeder::class,
            UpdateSeetingsModuleNameSeeder::class,
            CreatePflichtstundenHelpSiteSeeder::class,
            DeleteLogsPermissionSeeder::class,
            CreateStundenplanModuleSeeder::class,
            StundenplanSettingsSeeder::class,
            UpdateNachrichtenModuleLinkSeeder::class,
        ]);
    }
}
