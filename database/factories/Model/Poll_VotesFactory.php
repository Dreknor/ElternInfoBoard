<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Poll_Votes;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'poll_id' => \App\REPLACE_THIS::factory(),
            'author_id' => \App\REPLACE_THIS::factory(),
        ];
    }
}
