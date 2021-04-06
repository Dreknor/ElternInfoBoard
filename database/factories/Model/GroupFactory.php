<?php

namespace Database\Factories\Model;

use App\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Model\Group::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->monthName,
            'protected' => $this->faker->boolean,
            'bereich' => $this->faker->randomElement(['', 'Grundschule', 'Oberschule', 'Sonstiges']),
        ];
    }
}
