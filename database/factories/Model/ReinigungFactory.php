<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Reinigung;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'users_id' => \App\Model\User::factory(),
            'bereich' => $this->faker->word(),
            'datum' => $this->faker->date(),
        ];
    }
}
