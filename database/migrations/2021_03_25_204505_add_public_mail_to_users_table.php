<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPublicMailToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('publicMail')->after('email')->nullable();
        });

        \Illuminate\Support\Facades\DB::table('settings')->where('setting', 'Gruppen')->update([
           'options' => '{"active":"0","rights":[],"nav":{"name":"Gruppen","link":"groups","icon":"fas fa-user-friends"}}'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
