<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->string('publicMail')->after('email')->nullable();
        });

        DB::table('settings_modules')->where('setting', 'Gruppen')->update([
            'options' => '{"active":"0","rights":[],"nav":{"name":"Gruppen","link":"groups","icon":"fas fa-user-friends"}}',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
    }
};
