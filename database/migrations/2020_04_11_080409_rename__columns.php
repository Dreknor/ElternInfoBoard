<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('groups_termine', function (Blueprint $table) {
            $table->renameColumn('groups_id', 'group_id');
        });

        Schema::table('groups_user', function (Blueprint $table) {
            $table->renameColumn('groups_id', 'group_id');
        });

        Schema::table('groups_listen', function (Blueprint $table) {
            $table->renameColumn('groups_id', 'group_id');
        });
        Schema::table('rueckmeldungen', function (Blueprint $table) {
            $table->renameColumn('posts_id', 'post_id');
        });

        Schema::table('groups_posts', function (Blueprint $table) {
            $table->renameColumn('groups_id', 'group_id');
            $table->renameColumn('posts_id', 'post_id');
        });

        Schema::rename('groups_user', 'group_user');
        Schema::rename('groups_termine', 'group_termine');
        Schema::rename('groups_listen', 'group_listen');
        Schema::rename('groups_posts', 'group_post');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('group_user', 'groups_user');

        Schema::table('groups_termine', function (Blueprint $table) {
            $table->renameColumn('group_id', 'groups_id');
        });
        Schema::table('groups_user', function (Blueprint $table) {
            $table->renameColumn('group_id', 'groups_id');
        });
        Schema::table('groups_listen', function (Blueprint $table) {
            $table->renameColumn('group_id', 'groups_id');
        });
        Schema::table('groups_posts', function (Blueprint $table) {
            $table->renameColumn('group_id', 'groups_id');
            $table->renameColumn('post_id', 'posts_id');
        });
    }
}
