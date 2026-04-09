<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphe Beziehung: kann Rueckmeldung ODER Post (ReadReceipt) ODER ChildCheckIn referenzieren
            $table->string('remindable_type');
            $table->unsignedBigInteger('remindable_id');

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('post_id')->nullable();

            $table->unsignedTinyInteger('level'); // 1, 2 oder 3
            $table->string('channel', 50);        // 'in_app', 'email', 'push', 'escalation'

            $table->timestamp('sent_at');

            // Indexes
            $table->index(['remindable_type', 'remindable_id'], 'idx_remindable');
            $table->index(['user_id', 'post_id'], 'idx_user_post');
            $table->index('level', 'idx_level');

            // Foreign Keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('post_id')->references('id')->on('posts')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminder_logs');
    }
};

