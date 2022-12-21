<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\listen_termine;

class listen_termineFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = listen_termine::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'listen_id' => \App\Model\Liste::factory(),
            'termin' => $this->faker->dateTime(),
            'reserviert_fuer' => \App\Model\User::factory(),
        ];
    }
}
