<?php

namespace Database\Factories\Model;

use App\Model\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

class SettingsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Module::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'setting' => $this->faker->word(),
            'category' => $this->faker->word(),
            'options' => $this->faker->word(),
        ];
    }
}
