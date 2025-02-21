<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('reactions')->insert(
            [
                ['name' => 'like'],
                ['name' => 'happy'],
                ['name' => 'sad'],
                ['name' => 'love'],
                ['name' => 'haha'],
                ['name' => 'wow'],
            ]
        );
    }
}
