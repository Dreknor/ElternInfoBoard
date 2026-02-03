<?php

namespace Database\Factories\Model;

use App\Model\Reinigung;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReinigungFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Reinigung::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'users_id' => \App\Model\User::factory(),
            'bereich' => $this->faker->word(),
            'datum' => $this->faker->date(),
        ];
    }
}
