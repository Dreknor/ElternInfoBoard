<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\UserRueckmeldungen;

class UserRueckmeldungenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserRueckmeldungen::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'post_id' => \App\Model\Post::factory(),
            'users_id' => \App\Model\User::factory(),
            'text' => $this->faker->text(),
            'rueckmeldung_number' => $this->faker->randomNumber(),
        ];
    }
}
