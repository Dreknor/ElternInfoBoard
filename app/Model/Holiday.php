<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'bundesland',
        'name',
        'start',
        'end',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start' => 'date',
            'end' => 'date',
        ];
    }

    /**
     * Scope a query to only include holidays for a specific year.
     */
    #[Scope]
    protected function forYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope a query to only include holidays within a date range.
     */
    #[Scope]
    protected function betweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start', [$startDate, $endDate])
                ->orWhereBetween('end', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start', '<=', $startDate)
                        ->where('end', '>=', $endDate);
                });
        });
    }

    /**
     * Check if a given date falls within this holiday.
     */
    public function includesDate($date): bool
    {
        $date = \Carbon\Carbon::parse($date);

        return $date->between($this->start, $this->end);
    }

    /**
     * Get the duration of the holiday in days.
     */
    public function getDurationAttribute(): int
    {
        return $this->start->diffInDays($this->end) + 1;
    }
}
