<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Comment;

class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Comment::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'body' => $this->faker->text(),
            'commentable_type' => $this->faker->word(),
            'commentable_id' => $this->faker->randomDigitNotNull(),
            'creator_type' => $this->faker->word(),
            'creator_id' => $this->faker->randomDigitNotNull(),
            '_lft' => $this->faker->randomNumber(),
            '_rgt' => $this->faker->randomNumber(),
            'parent_id' => \App\Model\Comment::factory(),
        ];
    }
}
