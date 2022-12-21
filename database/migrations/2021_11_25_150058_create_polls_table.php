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
    public function up(): void
    {
        Schema::create('polls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('author_id');
            $table->unsignedBigInteger('post_id');
            $table->string('poll_name');
            $table->text('description')->nullable();
            $table->date('ends');
            $table->unsignedInteger('max_number')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('users');
            $table->foreign('post_id')->references('id')->on('posts');
        });

        Schema::create('poll_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poll_id');
            $table->string('option', 240);
            $table->timestamps();

            $table->foreign('poll_id')->references('id')->on('polls');
        });

        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('poll_id');
            $table->unsignedBigInteger('author_id');
            $table->timestamps();

            $table->foreign('poll_id')->references('id')->on('polls');
            $table->foreign('author_id')->references('id')->on('users');
        });

        DB::table('permissions')->insert([
            'name' => 'create polls',
            'guard_name' => 'web',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('polls');
    }
};
