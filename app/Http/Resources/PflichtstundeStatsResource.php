<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class PflichtstundeStatsResource
 *
 * API Resource für Pflichtstunden-Statistiken.
 *
 * @package App\Http\Resources
 */
class PflichtstundeStatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'progress' => [
                'percent' => $this->resource['your_progress'],
                'total_minutes_completed' => $this->resource['total_minutes_completed'],
                'total_hours_completed' => $this->resource['total_hours_completed'],
                'required_minutes' => $this->resource['required_minutes'],
                'required_hours' => $this->resource['required_hours'],
                'open_minutes' => $this->resource['open_minutes'],
                'open_hours' => $this->resource['open_hours'],
                'is_completed' => $this->resource['your_progress'] >= 100,
            ],
            'ranking' => [
                'your_rank' => $this->resource['your_rank'],
                'total_families' => $this->resource['total_parents'],
                'avg_progress' => $this->resource['avg_progress'],
                'better_than_average' => $this->resource['your_progress'] > $this->resource['avg_progress'],
            ],
            'payment' => [
                'remaining_payment' => $this->resource['remaining_payment'],
                'currency' => '€',
            ],
        ];
    }
}

