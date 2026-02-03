<?php

namespace Database\Factories\Model;

use App\Model\Child;
use App\Model\ChildMandate;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildMandateFactory extends Factory
{
    protected $model = ChildMandate::class;

    public function definition(): array
    {
        return [
            'child_id' => Child::factory(),
            'user_id' => User::factory(),
            'start' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'end' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'start' => now()->subMonths(3),
            'end' => null,
        ]);
    }
}
