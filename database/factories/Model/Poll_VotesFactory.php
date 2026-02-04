<?php

namespace Database\Factories\Model;

use App\Model\Poll_Votes;
use Illuminate\Database\Eloquent\Factories\Factory;

class Poll_VotesFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Poll_Votes::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'poll_id' => \App\Model\Poll::factory(),
            'author_id' => \App\Model\User::factory(),
        ];
    }
}
