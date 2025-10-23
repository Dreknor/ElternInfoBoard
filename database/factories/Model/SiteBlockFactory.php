<?php

namespace Database\Factories\Model;

use App\Model\SiteBlock;
use App\Model\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteBlockFactory extends Factory
{
    protected $model = SiteBlock::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'block_type' => $this->faker->randomElement(['App\Model\Post', 'App\Model\File']),
            'block_id' => 1,
            'position' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->sentence(3),
        ];
    }
}

