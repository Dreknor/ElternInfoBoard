<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Liste;

class ListeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Liste::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'listenname' => $this->faker->word(),
            'type' => $this->faker->word(),
            'duration' => $this->faker->randomNumber(),
            'besitzer' => \App\Model\User::factory(),
            'visible_for_all' => $this->faker->boolean(),
            'active' => $this->faker->boolean(),
            'ende' => $this->faker->date(),
            'multiple' => $this->faker->boolean(),
        ];
    }
}
