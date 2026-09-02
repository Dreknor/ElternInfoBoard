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
        Schema::create('late_pickups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_check_in_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->time('expected_time');
            $table->dateTime('picked_up_at');
            $table->unsignedInteger('delay_minutes');
            $table->string('status')->default('offen'); // offen, bestaetigt, verworfen
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('review_comment')->nullable();
            $table->timestamps();

            $table->unique(['child_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_pickups');
    }
};
