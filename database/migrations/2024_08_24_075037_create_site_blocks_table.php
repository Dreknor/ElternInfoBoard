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
    public function up()
    {
        Schema::create('site_blocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->string('title')->nullable();

            $table->unsignedBigInteger('block_id');
            $table->string('block_type');
            $table->integer('position');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
        });

        Schema::create('sites_blocks_text', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable();

            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('sites_blocks_image', function (Blueprint $table) {
            $table->id();

            $table->timestamps();
            $table->softDeletes();

        });

        Schema::create('sites_blocks_files', function (Blueprint $table) {
            $table->id();

            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sites_blocks_text');
        Schema::dropIfExists('sites_blocks_image');
        Schema::dropIfExists('sites_blocks_files');
        Schema::dropIfExists('site_blocks');
    }
};
