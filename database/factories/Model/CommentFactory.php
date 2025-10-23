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
            'title' => $this->faker->sentence(),
            'commentable_type' => \App\Model\Post::class,
            'commentable_id' => \App\Model\Post::factory(),
            'creator_type' => \App\Model\User::class,
            'creator_id' => \App\Model\User::factory(),
            '_lft' => $this->faker->numberBetween(1, 100),
            '_rgt' => $this->faker->numberBetween(1, 100),
            'parent_id' => null,
        ];
    }
}
