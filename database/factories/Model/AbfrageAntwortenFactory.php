<?php

namespace Database\Factories\Model;

use App\Model\AbfrageAntworten;
use Illuminate\Database\Eloquent\Factories\Factory;

class AbfrageAntwortenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AbfrageAntworten::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'rueckmeldung_id' => \App\Model\Rueckmeldungen::factory(),
            'user_id' => \App\Model\User::factory(),
            'option_id' => \App\Model\AbfrageOptions::factory(),
        ];
    }
}
