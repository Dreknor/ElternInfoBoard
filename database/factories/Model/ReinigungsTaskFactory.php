<?php

namespace Database\Factories\Model;

use App\Model\ReinigungsTask;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReinigungsTaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReinigungsTask::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'task' => $this->faker->text(),
        ];
    }
}
