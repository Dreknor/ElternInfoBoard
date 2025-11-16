<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::create('event_attendees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('elternrat_events')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['accepted', 'declined', 'maybe'])->default('maybe');
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->unique(['event_id', 'user_id']);
                $table->index('event_id');
                $table->index('user_id');
            });
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            Log::error('Failed to create event_attendees table: ' . $e->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_attendees');
    }
};
