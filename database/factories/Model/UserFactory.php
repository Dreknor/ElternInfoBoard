<?php

namespace Database\Factories\Model;

use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

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
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->email(),
            'password' => Hash::make('password'),
            'changePassword' => false, // zufällige Werte lösen PasswordExpired-Redirects (302) in Feature-Tests aus
            'benachrichtigung' => $this->faker->word(),
            'sendCopy' => $this->faker->boolean(),
            'last_online_at' => $this->faker->dateTime(),
            'track_login' => $this->faker->boolean(),
        ];
    }

    /**
     * Schülerkonto: keine E-Mail, Login über Benutzername (docs/konzept-schuelerkonten.md §3).
     */
    public function student(): static
    {
        return $this->state(fn (array $attributes) => [
            'account_type' => \App\Model\User::ACCOUNT_STUDENT,
            'email' => null,
            'username' => \Illuminate\Support\Str::lower(\Illuminate\Support\Str::slug($this->faker->unique()->firstName().' '.$this->faker->lastName(), '.')).$this->faker->unique()->numberBetween(1, 99999),
            'email_verified_at' => null,
        ]);
    }
}
