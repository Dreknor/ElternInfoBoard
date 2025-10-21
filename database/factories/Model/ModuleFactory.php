<?php

namespace Database\Factories\Model;

use App\Model\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        return [
            'setting' => $this->faker->word(),
            'category' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'options' => [],
        ];
    }

    public function withOptions(): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => [
                'enabled' => true,
                'setting1' => $this->faker->word(),
            ],
        ]);
    }
}

