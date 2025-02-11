<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');

            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();

            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('groups')->onDelete('set null');
            $table->foreign('class_id')->references('id')->on('groups')->onDelete('set null');
        });

        Schema::create('child_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('childs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('child_check_ins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('child_id');
            $table->boolean('checked_in')->default(false);
            $table->boolean('checked_out')->default(false);
            $table->date('date');
            $table->timestamps();

            $table->foreign('child_id')->references('id')->on('childs')->onDelete('cascade');
        });

        Schema::table('schickzeiten', function (Blueprint $table) {
            $table->string('child_name')->nullable()->change();
            $table->unsignedBigInteger('child_id')->nullable();

            $table->foreign('child_id')->references('id')->on('childs')->onDelete('set null');
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists('child_user');
        Schema::dropIfExists('child_check_ins');
        Schema::table('schickzeiten', function (Blueprint $table) {
            $table->string('child_name')->change();
            $table->dropForeign(['child_id']);
            $table->dropColumn('child_id');

        });
        Schema::dropIfExists('children');
    }
};
