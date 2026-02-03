<?php

namespace Database\Factories\Model;

use App\Model\Schickzeiten;
use Illuminate\Database\Eloquent\Factories\Factory;

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
     */
    public function definition(): array
    {
        return [
            'users_id' => \App\Model\User::factory(),
            'child_name' => $this->faker->word(),
            'changedBy' => $this->faker->randomNumber(),
        ];
    }
}
