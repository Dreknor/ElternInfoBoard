<?php

namespace Database\Factories\Model;

use App\Model\Pflichtstunde;
use Illuminate\Database\Eloquent\Factories\Factory;

class PflichtstundeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Pflichtstunde::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $end = $this->faker->dateTimeBetween($start, '+4 hours');

        return [
            'user_id' => \App\Model\User::factory(),
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

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => false,
            'rejected' => false,
            'approved_at' => null,
            'approved_by' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => true,
            'approved_at' => now(),
            'approved_by' => \App\Model\User::factory(),
            'rejected' => false,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'approved' => false,
            'rejected' => true,
            'rejected_at' => now(),
            'rejected_by' => \App\Model\User::factory(),
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }
}
