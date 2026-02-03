<?php

namespace Database\Factories\Model;

use App\Model\Losung;
use Illuminate\Database\Eloquent\Factories\Factory;

class LosungFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Losung::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'date' => $this->faker->date(),
            'Losungsvers' => $this->faker->word(),
            'Losungstext' => $this->faker->text(),
            'Lehrtextvers' => $this->faker->word(),
            'Lehrtext' => $this->faker->text(),
        ];
    }
}
