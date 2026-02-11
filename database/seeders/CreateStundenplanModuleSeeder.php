<?php

namespace Database\Seeders;

use App\Model\Module;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateStundenplanModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions for stundenplan
        $permissions = [
            'view stundenplan',
            'edit stundenplan',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);

            // Assign to admin role
            $adminRole = Role::where('name', 'admin')->first();
            if ($adminRole && !$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
            }

            // Assign view permission to lehrer role
            if ($permissionName === 'view stundenplan') {
                $lehrerRole = Role::where('name', 'lehrer')->first();
                if ($lehrerRole && !$lehrerRole->hasPermissionTo($permission)) {
                    $lehrerRole->givePermissionTo($permission);
                }
            }
        }

        // Create module entry using Module model
        $module = Module::firstOrCreate(
            ['setting' => 'Stundenplan'],
            [
                'description' => 'Zeigt den Stundenplan für Klassen, Lehrer und Räume an',
                'category' => 'module',
                'options' => [
                    'nav' => [
                        'icon' => 'fas fa-calendar-alt',
                        'link' => 'stundenplan',
                        'name' => 'Stundenplan',
                        'bottom-nav' => 'true'
                    ],
                    'active' => '1',
                    'rights' => [
                        'view stundenplan'
                    ]
                ]
            ]
        );

        if ($module->wasRecentlyCreated) {
            if ($this->command) {
                $this->command->info('Stundenplan module created successfully.');
            }
        } else {
            if ($this->command) {
                $this->command->info('Stundenplan module already exists.');
            }
        }
    }
}

