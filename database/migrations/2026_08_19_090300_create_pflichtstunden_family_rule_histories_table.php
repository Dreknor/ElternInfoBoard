<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pflichtstunden_family_rule_histories')) {
            return;
        }
        Schema::create('pflichtstunden_family_rule_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pflichtstunden_family_rule_id');
            $table->string('family_key', 64);
            $table->unsignedInteger('period_year');
            $table->string('from_mode', 20)->nullable();
            $table->string('to_mode', 20);
            $table->decimal('from_custom_required_hours', 6, 2)->nullable();
            $table->decimal('to_custom_required_hours', 6, 2)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('pflichtstunden_family_rule_id', 'ps_family_rule_hist_rule_fk')
                ->references('id')
                ->on('pflichtstunden_family_rules')
                ->onDelete('cascade');
            $table->index(['family_key', 'period_year'], 'ps_family_rule_hist_family_year_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pflichtstunden_family_rule_histories');
    }
};
