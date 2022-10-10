<?php

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
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->addColumn('boolean', 'reactable')->after('sticky');
        });

        DB::table('reactions')->insert(
            [
                ['name' => 'like'],
                ['name' => 'happy'],
                ['name' => 'sad'],
                ['name' => 'love'],
                ['name' => 'haha'],
                ['name' => 'wow'],
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->removeColumn('reactable');
        });
    }
};
