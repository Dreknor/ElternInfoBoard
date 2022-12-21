<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\krankmeldungen;

class krankmeldungenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = krankmeldungen::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'users_id' => \App\Model\User::factory(),
            'start' => $this->faker->date(),
            'ende' => $this->faker->date(),
            'name' => $this->faker->name(),
            'kommentar' => $this->faker->text(),
        ];
    }
}
