<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\AbfrageOptions;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'rueckmeldung_id' => \App\REPLACE_THIS::factory(),
            'type' => $this->faker->word(),
            'option' => $this->faker->text(),
        ];
    }
}
