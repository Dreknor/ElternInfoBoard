<?php

namespace Database\Factories\Model;

use App\Model\Poll;
use Illuminate\Database\Eloquent\Factories\Factory;

class PollFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Poll::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'author_id' => \App\Model\User::factory(),
            'post_id' => \App\Model\Post::factory(),
            'poll_name' => $this->faker->word(),
            'ends' => $this->faker->date(),
            'max_number' => $this->faker->randomNumber(),
        ];
    }
}
