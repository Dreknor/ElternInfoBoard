<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
