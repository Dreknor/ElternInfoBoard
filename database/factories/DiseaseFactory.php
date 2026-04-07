<?php

namespace Database\Factories;

use App\Model\Disease;
use Illuminate\Database\Eloquent\Factories\Factory;

class DiseaseFactory extends Factory
{
    protected $model = Disease::class;

    public function definition(): array
    {
        $diseases = [
            'Masern',
            'Mumps',
            'Röteln',
            'Windpocken',
            'Scharlach',
            'Keuchhusten',
            'Kopfläuse',
            'Magen-Darm-Infekt',
        ];

        return [
            'name' => $this->faker->unique()->randomElement($diseases),
            'reporting' => $this->faker->boolean(80), // 80% meldepflichtig
            'wiederzulassung_durch' => $this->faker->randomElement([
                'ärztliches Attest',
                'Elternbestätigung',
                'Gesundheitsamt-Freigabe',
            ]),
            'wiederzulassung_wann' => $this->faker->randomElement([
                'nach 24h Symptomfreiheit',
                'nach ärztlicher Behandlung',
                'nach vollständiger Genesung',
                'nach 7 Tagen',
                'nach 14 Tagen',
            ]),
            'aushang_dauer' => $this->faker->randomElement([7, 14, 21, 28]),
        ];
    }
}
