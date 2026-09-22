<?php

namespace Database\Factories\Model;

use App\Model\SearchLog;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SearchLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SearchLog::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $nachrichten = $this->faker->numberBetween(0, 5);
        $seiten = $this->faker->numberBetween(0, 3);

        return [
            'user_id' => User::factory(),
            'search_term' => $this->faker->word(),
            'nachrichten_count' => $nachrichten,
            'seiten_count' => $seiten,
            'results_count' => $nachrichten + $seiten,
            'created_at' => now(),
        ];
    }

    public function withoutResults(): static
    {
        return $this->state(fn () => [
            'nachrichten_count' => 0,
            'seiten_count' => 0,
            'results_count' => 0,
        ]);
    }
}
