<?php

namespace Database\Factories\Model;

use App\Model\Mail;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Mail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'senders_id' => \App\Model\User::factory(),
            'to' => $this->faker->word(),
        ];
    }
}
