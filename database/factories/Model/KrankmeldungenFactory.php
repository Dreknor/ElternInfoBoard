<?php

namespace Database\Factories\Model;

use App\Model\Krankmeldungen;
use Illuminate\Database\Eloquent\Factories\Factory;

class KrankmeldungenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Krankmeldungen::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
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
