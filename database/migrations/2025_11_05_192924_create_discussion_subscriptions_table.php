<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::create('discussion_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('discussion_id')->constrained()->onDelete('cascade');
                $table->boolean('email_notifications')->default(true);
                $table->boolean('web_notifications')->default(true);
                $table->timestamps();

                $table->unique(['user_id', 'discussion_id']);
                $table->index('user_id');
                $table->index('discussion_id');
            });
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            \Illuminate\Support\Facades\Log::error('Failed to create discussion_subscriptions table: '.$e->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussion_subscriptions');
    }
};
