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
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('search_term', 100);

            $table->unsignedInteger('nachrichten_count')->default(0);
            $table->unsignedInteger('seiten_count')->default(0);
            $table->unsignedInteger('results_count')->default(0);

            $table->timestamp('created_at')->nullable();

            $table->index('created_at', 'idx_search_logs_created_at');
            $table->index('search_term', 'idx_search_logs_search_term');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_logs');
    }
};
