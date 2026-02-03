<?php

namespace Database\Factories\Model;

use App\Model\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'header' => $this->faker->sentence(),
            'news' => $this->faker->text(),
            'author' => \App\Model\User::factory(),
            'released' => $this->faker->boolean(),
            'sticky' => $this->faker->boolean(),
            'reactable' => $this->faker->boolean(),
            'type' => $this->faker->randomElement(['news', 'announcement', 'info']),
            'external' => $this->faker->boolean(),
            'read_receipt' => $this->faker->boolean(),
            'no_header' => $this->faker->boolean(),
        ];
    }
}
