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
        Schema::create('rueckmeldungen', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('post_id');
            $table->string('empfaenger')->default('info@esz-radebeul.de');
            $table->date('ende');
            $table->longText('text');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('post_id')->references('id')->on('posts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {

        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->dropForeign(['post_id']);
        });

        Schema::dropIfExists('rueckmeldungen');
    }
};
