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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->foreign('author_id')->references('id')->on('users')->onDelete('set null');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('site_id');
            $table->foreign('site_id')->references('id')->on('sites')->onDelete('cascade');
            $table->unsignedBigInteger('group_id');
            $table->foreign('group_id')->references('id')->on('groups')->onDelete('cascade');
            $table->timestamps();
        });

        \Spatie\Permission\Models\Permission::create(['name' => 'view sites']);
        \Spatie\Permission\Models\Permission::create(['name' => 'create sites']);

        \App\Model\Module::create([
            'setting' => 'Seiten',
            'description' => 'Erlaubt das Anlegen und Verwalten von Seiten',
            'category' => 'module',
            'options' => [
                "active"=>"0",
                "rights" => ["view sites"],
                "nav"=>[
                    "name"=>"Seiten",
                    "link"=>"sites",
                    "icon"=>"fa fa-file",
                    "bottom-nav"=>"false"
                ],
                "adm-nav"=>[
                    "adm-rights"=>["create sites"],
                    "name"=>"neue Seite",
                    "link"=>"sites/create",
                    "icon"=>"fa fa-file-pen"
                ]
            ],
            ]);

        \Illuminate\Support\Facades\Artisan::call('cache:clear');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::dropIfExists('site_group');
        Schema::dropIfExists('sites');
        \Spatie\Permission\Models\Permission::where('name','view sites')->delete();
        \Spatie\Permission\Models\Permission::where('name','create sites')->delete();
        \App\Model\Module::where('setting', 'Seiten')->delete();
    }
};
