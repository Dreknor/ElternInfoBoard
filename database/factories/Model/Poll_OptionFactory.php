<?php

namespace Database\Factories\Model;

use App\Model\Poll_Option;
use Illuminate\Database\Eloquent\Factories\Factory;

class Poll_OptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Poll_Option::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'poll_id' => \App\Model\Poll::factory(),
            'option' => $this->faker->word(),
        ];
    }
}
