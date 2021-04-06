<?php

namespace Database\Factories\Model;

use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

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
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'publicMail' => $this->faker->optional()->email,
            'changePassword' => $this->faker->randomElement([0,1]),
            'benachrichtigung' => $this->faker->randomElement([0,1]),
            'lastEmail' => $this->faker->dateTimeBetween('-4 weeks', 'now'),
            'sendCopy'=> $this->faker->randomElement([0,1]),
            'track_login' => $this->faker->randomElement([0,1]),
            'last_online_at'    => $this->faker->dateTimeBetween('-1 week', 'now'),
            'changeSettings'    => $this->faker->randomElement([0,1]),
        ];
    }
}
