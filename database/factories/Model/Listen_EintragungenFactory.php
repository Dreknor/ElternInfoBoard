<?php

namespace Database\Factories\Model;

use App\Model\Listen_Eintragungen;
use Illuminate\Database\Eloquent\Factories\Factory;

class Listen_EintragungenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Listen_Eintragungen::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'listen_id' => \App\Model\Liste::factory(),
            'user_id' => \App\Model\User::factory(),
            'created_by' => \App\Model\User::factory(),
        ];
    }
}
