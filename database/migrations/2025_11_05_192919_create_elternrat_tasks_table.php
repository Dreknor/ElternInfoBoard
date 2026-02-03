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
            Schema::create('elternrat_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
                $table->enum('status', ['open', 'in_progress', 'completed'])->default('open');
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->date('due_date')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index('status');
                $table->index('assigned_to');
                $table->index('due_date');
            });
        } catch (\Exception $e) {
            // Log the exception or handle it as needed
            Log::error('Failed to create elternrat_tasks table: '.$e->getMessage());
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elternrat_tasks');
    }
};
