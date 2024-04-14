<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vertretungen', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('klasse'); // entspricht der Gruppe
            $table->string('stunde');
            $table->string('altFach');
            $table->string('neuFach')->nullable();
            $table->string('lehrer')->nullable();
            $table->string('comment', 240)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('vertretungen');
    }
};
