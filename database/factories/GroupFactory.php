<?php

namespace Database\Factories;

use App\Model\Group;
use Illuminate\Database\Eloquent\Factories\Factory;

class GroupFactory extends Factory
{
    protected $model = Group::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->word(),
            'bereich'    => 'Klasse',
            'protected'  => false,
            'ucs_source' => 'local',
        ];
    }

    public function kelvinClass(string $name = null, string $school = 'GS-XY'): static
    {
        $n = $name ?? $this->faker->numerify('#').$this->faker->randomLetter();

        return $this->state([
            'name'          => $n,
            'bereich'       => 'Klasse',
            'ucs_source'    => 'kelvin',
            'ucs_class_url' => "https://ucs.example.de/ucsschool/kelvin/v1/classes/{$school}:{$n}",
            'ucs_synced_at' => now(),
        ]);
    }
}

