<?php

namespace Database\Factories\Model;

use App\Model\Poll_Answers;
use Illuminate\Database\Eloquent\Factories\Factory;

class Poll_AnswersFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Poll_Answers::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'poll_id' => \App\Model\Poll::factory(),
            'option_id' => \App\Model\Poll_Option::factory(),
        ];
    }
}
