<?php

namespace Database\Factories;

use App\Model\Child;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [
            'first_name'  => $this->faker->firstName(),
            'last_name'   => $this->faker->lastName(),
            'ucs_source'  => 'local',
            'notification' => false,
            'auto_checkIn' => false,
        ];
    }

    public function fromKelvin(string $username = null, string $school = 'GS-XY'): static
    {
        return $this->state([
            'ucs_source'    => 'kelvin',
            'ucs_username'  => $username ?? $this->faker->userName(),
            'ucs_school'    => $school,
            'ucs_uuid'      => $this->faker->uuid(),
            'ucs_synced_at' => now(),
        ]);
    }
}

