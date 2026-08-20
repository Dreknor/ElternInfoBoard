<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pflichtstunden_family_rules', function (Blueprint $table) {
            $table->id();
            $table->string('family_key', 64);
            $table->unsignedInteger('period_year');
            $table->enum('mode', ['standard', 'reduced', 'custom'])->default('standard');
            $table->decimal('custom_required_hours', 6, 2)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['family_key', 'period_year']);
            $table->index(['period_year', 'mode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pflichtstunden_family_rules');
    }
};
