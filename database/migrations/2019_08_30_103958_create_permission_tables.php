<?php

use App\Model\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type']);

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            $table->unsignedInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type']);

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames) {
            $table->unsignedInteger('permission_id');
            $table->unsignedInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id']);
        });

        DB::table($tableNames['permissions'])->insert([
            [
                'name' => 'edit permission',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit user',
                'guard_name' => 'web',
            ],
            [
                'name' => 'create posts',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view all',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit posts',
                'guard_name' => 'web',
            ],
            [
                'name' => 'upload files',
                'guard_name' => 'web',
            ],
            [
                'name' => 'import user',
                'guard_name' => 'web',
            ],
            [
                'name' => 'release posts',
                'guard_name' => 'web',
            ],
            [
                'name' => 'use scriptTag',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view elternrat',
                'guard_name' => 'web',
            ],
            [
                'name' => 'send urgent message',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit reinigung',
                'guard_name' => 'web',
            ],
            [
                'name' => 'upload great files',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit termin',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit terminliste',
                'guard_name' => 'web',
            ],
            [
                'name' => 'create terminliste',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view protected',
                'guard_name' => 'web',
            ],
            [
                'name' => 'add changelog',
                'guard_name' => 'web',
            ],
            [
                'name' => 'set password',
                'guard_name' => 'web',
            ],
            [
                'name' => 'make sticky',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view reinigung',
                'guard_name' => 'web',
            ],
            [
                'name' => 'delete elternrat file',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view rueckmeldungen',
                'guard_name' => 'web',
            ],
            [
                'name' => 'download schickzeiten',
                'guard_name' => 'web',
            ],
            [
                'name' => 'edit schickzeiten',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view schickzeiten',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view krankmeldung',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view groups',
                'guard_name' => 'web',
            ],
            [
                'name' => 'view mitarbeiterboard',
                'guard_name' => 'web',
            ],
            [
                'name' => 'loginAsUser',
                'guard_name' => 'web',
            ],
        ]);

        DB::table($tableNames['roles'])->insert([
            [
                'name' => 'Administrator',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Mitarbeiter',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Elternrat',
                'guard_name' => 'web',
            ],
            [
                'name' => 'Sekretariat',
                'guard_name' => 'web',
            ],
        ]);

        DB::table($tableNames['model_has_permissions'])->insert([
            [
                'permission_id' => 1,
                'model_type' => User::class,
                'model_id' => 1, ],
            [
                'permission_id' => 2,
                'model_type' => User::class,
                'model_id' => 1,
            ],
        ]);

        DB::table($tableNames['model_has_roles'])->insert([
            [
                'role_id' => 1,
                'model_type' => User::class,
                'model_id' => 1,
            ],
            [
                'role_id' => 2,
                'model_type' => User::class,
                'model_id' => 1,
            ],
        ]);

        DB::table($tableNames['role_has_permissions'])->insert([
            [
                'permission_id' => 1,
                'role_id' => 1,
            ],
            [
                'permission_id' => 2,
                'role_id' => 1,
            ],
            [
                'permission_id' => 10,
                'role_id' => 3,
            ],
        ]);

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
