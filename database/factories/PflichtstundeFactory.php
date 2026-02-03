<?php

namespace Database\Factories;

use App\Model\Pflichtstunde;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PflichtstundeFactory extends Factory
{
    protected $model = Pflichtstunde::class;

    public function definition()
    {
        $start = $this->faker->dateTimeBetween('-1 year', 'now');
        $end = (clone $start)->modify('+'.$this->faker->numberBetween(1, 8).' hours');

        return [
            'user_id' => User::factory(),
            'description' => $this->faker->sentence(),
            'start' => $start,
            'end' => $end,
            'approved' => false,
            'rejected' => false,
        ];
    }

    public function pending()
    {
        return $this->state(function (array $attributes) {
            return [
                'approved' => false,
                'rejected' => false,
                'approved_at' => null,
                'rejected_at' => null,
            ];
        });
    }

    public function approved()
    {
        return $this->state(function (array $attributes) {
            return [
                'approved' => true,
                'approved_at' => now(),
                'approved_by' => User::factory(),
                'rejected' => false,
            ];
        });
    }

    public function rejected()
    {
        return $this->state(function (array $attributes) {
            return [
                'rejected' => true,
                'rejected_at' => now(),
                'rejected_by' => User::factory(),
                'rejection_reason' => $this->faker->sentence(),
                'approved' => false,
            ];
        });
    }
}
