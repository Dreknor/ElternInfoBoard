<?php

namespace Database\Factories\Model;

use App\Model\ActiveDisease;
use App\Model\Disease;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiveDiseaseFactory extends Factory
{
    protected $model = ActiveDisease::class;

    public function definition(): array
    {
        return [
            'disease_id' => Disease::factory(),
            'user_id' => User::factory(),
            'start' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'end' => $this->faker->dateTimeBetween('now', '+2 weeks'),
            'comment' => $this->faker->optional()->sentence(),
            'active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
