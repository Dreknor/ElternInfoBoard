<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Schickzeiten;

class SchickzeitenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Schickzeiten::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'users_id' => \App\Model\User::factory(),
            'child_name' => $this->faker->word(),
            'changedBy' => $this->faker->randomNumber(),
        ];
    }
}
