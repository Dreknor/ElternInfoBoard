<?php

namespace Database\Factories\Model;

use App\Model\Disease;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiseaseFactory extends Factory
{
    protected $model = Disease::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'reporting' => $this->faker->boolean(),
            'wiederzulassung_durch' => $this->faker->sentence(),
            'wiederzulassung_wann' => $this->faker->sentence(),
            'aushang_dauer' => $this->faker->numberBetween(1, 30),
        ];
    }

    public function reportable(): static
    {
        return $this->state(fn (array $attributes) => [
            'reporting' => true,
        ]);
    }
}

