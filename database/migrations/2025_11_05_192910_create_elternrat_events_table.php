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
            Schema::create('elternrat_events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->string('location')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->boolean('send_reminder')->default(true);
                $table->integer('reminder_hours')->default(24); // Stunden vor Event
                $table->timestamps();

                $table->index('start_time');
                $table->index('created_by');
            });
        } catch (\Exception $e) {
            // Log the error message
            Log::error('Error creating elternrat_events table: '.$e->getMessage());

        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elternrat_events');
    }
};
