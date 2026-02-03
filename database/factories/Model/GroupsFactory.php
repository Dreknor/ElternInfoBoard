<?php

namespace Database\Factories\Model;

use App\Model\Groups;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Groups::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'protected' => $this->faker->boolean(),
        ];
    }
}
