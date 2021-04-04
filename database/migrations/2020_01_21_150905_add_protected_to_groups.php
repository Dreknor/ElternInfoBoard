<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProtectedToGroups extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->addColumn('boolean', 'protected')->default(0)->after('name');
        });

        \Illuminate\Support\Facades\DB::table('groups')->insert([
            [
                'name' => 'Elternrat',
                'protected'=>1,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->removeColumn('protected');
        });
    }
}
