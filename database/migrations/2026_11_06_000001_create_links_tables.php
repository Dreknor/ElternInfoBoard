<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modul „Links“: von der Verwaltung gepflegte (interne und externe) Links für Nutzer.
 * Sichtbar für alle Nutzer (`for_all`) oder für ausgewählte Gruppen (`link_group`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('url', 2048);
            $table->string('description', 500)->nullable();
            $table->string('category', 80)->nullable();
            $table->string('icon', 60)->nullable();
            $table->boolean('for_all')->default(false);
            $table->boolean('pinned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'position']);
        });

        Schema::create('link_group', function (Blueprint $table) {
            $table->foreignId('link_id')->constrained('links')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->primary(['link_id', 'group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_group');
        Schema::dropIfExists('links');
    }
};
