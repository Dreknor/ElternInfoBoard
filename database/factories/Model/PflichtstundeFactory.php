<?php

namespace Database\Factories\Model;

use App\Model\Pflichtstunde;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PflichtstundeFactory extends Factory
{
    protected $model = Pflichtstunde::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 month', 'now');
        $end = (clone $start)->modify('+' . $this->faker->numberBetween(1, 4) . ' hours');

        return [
            'user_id' => User::factory(),
            'start' => $start,
            'end' => $end,
            'description' => $this->faker->sentence(),
            'approved' => false,
            'approved_at' => null,
            'approved_by' => null,
            'rejected' => false,
            'rejected_at' => null,
            'rejected_by' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'rejected' => true,
            'rejected_at' => now(),
            'rejected_by' => User::factory(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => false,
            'rejected' => false,
        ]);
    }
}

