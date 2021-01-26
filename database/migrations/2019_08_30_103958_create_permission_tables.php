<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
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
            $table->index([$columnNames['model_morph_key'], 'model_type', ]);

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
            $table->index([$columnNames['model_morph_key'], 'model_type', ]);

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

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        \Illuminate\Support\Facades\DB::table($tableNames['permissions'])->insert([
            [
                'name'  => 'edit permission',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit user',
                'guard' => 'web'
            ],
            [
                'name'  => 'create posts',
                'guard' => 'web'
            ],
            [
                'name'  => 'view all',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit posts',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit user',
                'guard' => 'web'
            ],
            [
                'name'  => 'upload files',
                'guard' => 'web'
            ],
            [
                'name'  => 'import user',
                'guard' => 'web'
            ],
            [
                'name'  => 'release posts',
                'guard' => 'web'
            ],
            [
                'name'  => 'use scriptTag',
                'guard' => 'web'
            ],
            [
                'name'  => 'view elternrat',
                'guard' => 'web'
            ],
            [
                'name'  => 'send urgent message',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit reinigung',
                'guard' => 'web'
            ],
            [
                'name'  => 'upload great files',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit termin',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit terminliste',
                'guard' => 'web'
            ],
            [
                'name'  => 'create terminliste',
                'guard' => 'web'
            ],
            [
                'name'  => 'view protected',
                'guard' => 'web'
            ],
            [
                'name'  => 'add changelog',
                'guard' => 'web'
            ],
            [
                'name'  => 'set password',
                'guard' => 'web'
            ],
            [
                'name'  => 'make sticky',
                'guard' => 'web'
            ],
            [
                'name'  => 'view reinigung',
                'guard' => 'web'
            ],
            [
                'name'  => 'delete elternrat file',
                'guard' => 'web'
            ],
            [
                'name'  => 'view rueckmeldungen',
                'guard' => 'web'
            ],
            [
                'name'  => 'download schickzeiten',
                'guard' => 'web'
            ],
            [
                'name'  => 'edit schickzeiten',
                'guard' => 'web'
            ],
            [
                'name'  => 'view schickzeiten',
                'guard' => 'web'
            ],
            [
                'name'  => 'view krankmeldung',
                'guard' => 'web'
            ],
            [
                'name'  => 'view groups',
                'guard' => 'web'
            ],
            [
                'name'  => 'view mitarbeiterboard',
                'guard' => 'web'
            ],
        ]);

        \Illuminate\Support\Facades\DB::table('model_has_permission')->insert([
           'permission_id' => 1,
            'model_type'    => 'App\Model\User',
            'model_id'  => 1
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = config('permission.table_names');

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
}
