<?php

namespace Database\Factories\Model;

use App\Model\Family;
use Illuminate\Database\Eloquent\Factories\Factory;

class FamilyFactory extends Factory
{
    protected $model = Family::class;

    public function definition(): array
    {
        return [
            'name' => 'Familie '.$this->faker->lastName(),
            'source' => Family::SOURCE_MANUAL,
            'is_locked' => false,
        ];
    }

    public function locked(): static
    {
        return $this->state(fn () => ['is_locked' => true]);
    }
}
