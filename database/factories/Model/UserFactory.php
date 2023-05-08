<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\User;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
            'password' => Hash::make('password'),
            'changePassword' => $this->faker->boolean(),
            'benachrichtigung' => $this->faker->word(),
            'sendCopy' => $this->faker->boolean(),
            'last_online_at' => $this->faker->dateTime(),
            'track_login' => $this->faker->boolean(),
            'user_id' => \App\Model\Group::factory(),
        ];
    }
}
