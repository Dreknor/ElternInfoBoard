<?php

namespace Database\Factories\Model;

use App\Model\group_post;
use Illuminate\Database\Eloquent\Factories\Factory;

class group_postFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = group_post::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'group_id' => \App\Model\Group::factory(),
            'post_id' => \App\Model\Post::factory(),
        ];
    }
}
