<?php

namespace Database\Factories\Model;

use App\Model\Termin;
use Illuminate\Database\Eloquent\Factories\Factory;

class TerminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Termin::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'terminname' => $this->faker->word(),
            'start' => $this->faker->dateTime(),
            'ende' => $this->faker->dateTime(),
            'author_id' => \App\Model\User::factory(),
            'description' => $this->faker->optional()->sentence(),
        ];
    }
}
