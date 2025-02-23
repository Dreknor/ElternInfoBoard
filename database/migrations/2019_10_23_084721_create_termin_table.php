<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('termine', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('terminname');
            $table->dateTime('start');
            $table->dateTime('ende');
            $table->timestamps();
        });

        Schema::create('group_termine', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('termin_id');

            $table->foreign('group_id')->references('id')->on('groups');
            $table->foreign('termin_id')->references('id')->on('termine');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {

        Schema::table('group_termine', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['termin_id']);
        });

        Schema::table('termine', function (Blueprint $table) {

           if (Schema::hasColumn('termine', 'group_id')) {
                $table->dropColumn('group_id');
            }

            if (Schema::hasColumn('termine', 'termin_id')) {
                $table->dropColumn('termin_id');
            }
        });

        Schema::dropIfExists('group_termine');
        Schema::dropIfExists('termine');
    }
};
