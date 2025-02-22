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
        Schema::create('active_diseases', function (Blueprint $table) {
            $table->id();
            $table->date('start');
            $table->date('end')->nullable();
            $table->string('comment')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamps();
            $table->foreignId('disease_id')->constrained();
            $table->foreignId('user_id')->constrained();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('active_diseases');
    }
};
