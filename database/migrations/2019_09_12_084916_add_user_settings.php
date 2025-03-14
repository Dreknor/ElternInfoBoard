<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('changePassword')->default(1);
            $table->string('benachrichtigung')->default('weekly');
            $table->dateTime('lastEmail')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('changePassword');
            $table->dropColumn('benachrichtigung');
            $table->dropColumn('lastEmail');
            $table->dropSoftDeletes();
        });
    }
};
