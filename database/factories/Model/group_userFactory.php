<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\group_user;

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
     *
     * @return array
     */
    public function definition()
    {
        return [
            'group_id' => \App\Model\Group::factory(),
            'user_id' => \App\Model\User::factory(),
        ];
    }
}
