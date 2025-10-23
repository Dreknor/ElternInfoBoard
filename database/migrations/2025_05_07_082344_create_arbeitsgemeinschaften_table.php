<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arbeitsgemeinschaften', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->integer('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('max_participants')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('arbeitsgemeinschaften_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ag_id');
            $table->foreign('ag_id')->references('id')->on('arbeitsgemeinschaften')->onDelete('cascade');
            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('arbeitsgemeinschaften_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ag_id');
            $table->foreign('ag_id')->references('id')->on('arbeitsgemeinschaften')->onDelete('cascade');
            $table->unsignedBigInteger('participant_id');
            $table->foreign('participant_id')->references('id')->on('children')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });

        $settings =
            [
                'setting' => 'Arbeitsgemeinschaften',
                'description' => 'Ermöglicht die Verwaltung von Arbeitsgemeinschaften und das Zuordnen von Teilnehmern durch Eltern',
                'category' => 'module',
                'options' => json_encode([
                    'active' => '0',
                    'rights' => [
                        'view GTA'
                    ],
                    'nav' => [
                        'name' => 'AGs',
                        'link' => 'arbeitsgemeinschaften',
                        'icon' => 'fas fa-user-friends',
                    ],
                    'adm-nav' => [
                        'adm-rights' => ['edit GTA'],
                        'name' => 'Arbeitsgemeinschaften',
                        'link' => 'verwaltung/arbeitsgemeinschaften',
                        'icon' => 'fas fa-user-friends',
                    ],
                ])
            ];

        DB::table('settings_modules')->insert($settings);

        $permission =[
            [
                'name' => 'edit GTA',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'view GTA',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('permissions')->insert($permission);

        Cache::clear();


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arbeitsgemeinschaften_participants');
        Schema::dropIfExists('arbeitsgemeinschaften_groups');
        Schema::dropIfExists('arbeitsgemeinschaften');
        DB::table('settings_modules')->where('setting', 'Arbeitsgemeinschaften')->delete();

        DB::table('permissions')->where('name', 'edit GTA')->delete();
        DB::table('permissions')->where('name', 'view GTA')->delete();

    }
};
