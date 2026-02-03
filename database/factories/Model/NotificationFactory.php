<?php

namespace Database\Factories\Model;

use App\Model\Notification;
use App\Model\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'read' => false,
            'type' => $this->faker->randomElement(['info', 'success', 'warning', 'error']),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => true,
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read' => false,
        ]);
    }
}
