<?php

namespace Database\Factories\Model;

use App\Model\group_user;
use Illuminate\Database\Eloquent\Factories\Factory;

class group_userFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = group_user::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'group_id' => \App\Model\Group::factory(),
            'user_id' => \App\Model\User::factory(),
        ];
    }
}
