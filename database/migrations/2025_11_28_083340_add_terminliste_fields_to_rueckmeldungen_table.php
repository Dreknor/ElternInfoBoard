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
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            if (! Schema::hasColumn('rueckmeldungen', 'liste_id')) {
                $table->foreignId('liste_id')->nullable()->after('post_id')->constrained('listen')->nullOnDelete();
            }

            if (! Schema::hasColumn('rueckmeldungen', 'terminliste_start_date')) {
                $table->date('terminliste_start_date')->nullable()->after('ende');
            }

            if (! Schema::hasColumn('rueckmeldungen', 'terminliste_end_date')) {
                $table->date('terminliste_end_date')->nullable()->after('terminliste_start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            if (Schema::hasColumn('rueckmeldungen', 'terminliste_end_date')) {
                $table->dropColumn('terminliste_end_date');
            }

            if (Schema::hasColumn('rueckmeldungen', 'terminliste_start_date')) {
                $table->dropColumn('terminliste_start_date');
            }

            if (Schema::hasColumn('rueckmeldungen', 'liste_id')) {
                $table->dropForeign(['liste_id']);
                $table->dropColumn('liste_id');
            }
        });
    }
};
