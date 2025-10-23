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
        if (!Schema::hasTable('child_mandates')) {
            Schema::create('child_mandates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('child_id');
                $table->string('mandate_name');
                $table->text('mandate_description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('child_id')->references('id')->on('children')->onDelete('cascade');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('SET NULL');
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('child_mandates');
    }
};
