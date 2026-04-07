<?php

namespace Database\Factories\Model;

use App\Model\AbfrageOptions;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbfrageOptionsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AbfrageOptions::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'rueckmeldung_id' => \App\Model\Rueckmeldungen::factory(),
            'type' => $this->faker->word(),
            'option' => $this->faker->text(),
        ];
    }
}
