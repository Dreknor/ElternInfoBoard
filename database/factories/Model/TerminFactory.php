<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Termin;

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
    public function definition()
    {
        return [
            'terminname' => $this->faker->word(),
            'start' => $this->faker->dateTime(),
            'ende' => $this->faker->dateTime(),
        ];
    }
}
