<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Post;

class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'header' => $this->faker->word(),
            'news' => $this->faker->text(),
            'released' => $this->faker->boolean(),
            'sticky' => $this->faker->boolean(),
            'reactable' => $this->faker->boolean(),
            'type' => $this->faker->word(),
        ];
    }
}
