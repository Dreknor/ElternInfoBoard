<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
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
        Schema::create('mails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('senders_id');
            $table->string('to');
            $table->text('subject')->nullable();
            $table->text('text')->nullable();
            $table->longText('file')->nullable();
            $table->timestamps();

            $table->foreign('senders_id')->references('id')->on('users')->cascadeOnDelete();

            DB::table('permissions')->insert([
                'name' => 'see mails',
                'guard_name' => 'web',
            ]);

            Artisan::call('cache:clear');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('mails');
    }
};
