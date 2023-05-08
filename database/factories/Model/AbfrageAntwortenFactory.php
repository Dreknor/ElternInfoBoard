<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\AbfrageAntworten;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'rueckmeldung_id' => \App\REPLACE_THIS::factory(),
            'user_id' => \App\REPLACE_THIS::factory(),
            'option_id' => \App\Model\AbfrageOptions::factory(),
        ];
    }
}
