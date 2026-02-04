<?php

namespace Database\Factories\Model;

use App\Model\Site;
use App\Model\SiteBlock;
use Illuminate\Database\Eloquent\Factories\Factory;

class SiteBlockFactory extends Factory
{
    protected $model = SiteBlock::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'block_type' => $this->faker->randomElement([\App\Model\Post::class, 'App\Model\File']),
            'block_id' => 1,
            'position' => $this->faker->numberBetween(1, 10),
            'title' => $this->faker->sentence(3),
        ];
    }
}
