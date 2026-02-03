<?php

namespace Database\Factories\Model;

use App\Model\Changelog;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChangelogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Changelog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'header' => $this->faker->word(),
            'text' => $this->faker->text(),
        ];
    }
}
