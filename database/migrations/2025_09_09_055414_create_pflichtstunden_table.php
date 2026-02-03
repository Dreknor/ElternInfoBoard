<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pflichtstunden', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->dateTime('start');
            $table->dateTime('end')->nullable();
            $table->string('description')->nullable();
            $table->boolean('approved')->default(false);
            $table->dateTime('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->boolean('rejected')->default(false);
            $table->dateTime('rejected_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('rejected_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('listen', function (Blueprint $table) {
            $table->boolean('pflichtstunden_erstellt')->default(false);
            $table->dateTime('pflichtstunden_start_at')->nullable();
            $table->dateTime('pflichtstunden_ende_at')->nullable();

        });

        $module = [
            'setting' => 'Pflichtstunden',
            'description' => 'Verwaltung der Elternpflichtstunden',
            'category' => 'module',
            'options' => json_encode([
                'active' => '0',
                'rights' => [
                    'view Pflichtstunden',
                ],
                'nav' => [
                    'name' => 'Pflichtstunden',
                    'link' => 'pflichtstunden',
                    'icon' => 'fas fa-clock',
                ],
                'adm-nav' => [
                    'adm-rights' => ['edit Pflichtstunden'],
                    'name' => 'Pflichtstunden',
                    'link' => 'verwaltung/pflichtstunden',
                    'icon' => 'fas fa-clock',
                ],
            ]),
        ];

        DB::table('settings_modules')->insert($module);

        \Spatie\Permission\Models\Permission::insert([
            ['name' => 'view Pflichtstunden', 'guard_name' => 'web'],
            ['name' => 'edit Pflichtstunden', 'guard_name' => 'web'],
        ]);

        \Illuminate\Support\Facades\Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pflichtstunden');
        Schema::table('listen', function (Blueprint $table) {
            $table->dropColumn('pflichtstunden_erstellt');
            $table->dropColumn('pflichtstunden_start_at');
            $table->dropColumn('pflichtstunden_ende_at');
        });

        DB::table('settings_modules')->where('setting', 'Pflichtstunden')->delete();

    }
};
