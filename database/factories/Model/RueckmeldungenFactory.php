<?php

namespace Database\Factories\Model;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Model\Rueckmeldungen;

class RueckmeldungenFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Rueckmeldungen::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'post_id' => \App\Model\Post::factory(),
            'type' => $this->faker->word(),
            'commentable' => $this->faker->boolean(),
            'empfaenger' => $this->faker->word(),
            'ende' => $this->faker->date(),
            'text' => $this->faker->text(),
            'max_answers' => $this->faker->randomNumber(),
        ];
    }
}
