<?php

namespace Database\Factories\Model;

use App\Model\Rueckmeldungen;
use Illuminate\Database\Eloquent\Factories\Factory;

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
    public function definition(): array
    {
        return [
            'post_id' => \App\Model\Post::factory(),
            'type' => $this->faker->word(),
            'commentable' => $this->faker->boolean(),
            'empfaenger' => $this->faker->word(),
            'ende' => $this->faker->date(),
            'text' => $this->faker->text(),
            'max_answers' => $this->faker->randomNumber(),
            'liste_id' => null,
            'terminliste_start_date' => null,
            'terminliste_end_date' => null,
        ];
    }

    /**
     * Indicate that the rueckmeldung is a terminliste type.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function terminliste()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'terminliste',
                'liste_id' => \App\Model\Liste::factory(),
                'terminliste_start_date' => now(),
                'terminliste_end_date' => now()->addWeek(),
                'text' => 'Terminbuchung',
            ];
        });
    }
}
