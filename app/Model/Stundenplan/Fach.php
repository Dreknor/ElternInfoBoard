<?php

namespace App\Model\Stundenplan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fach extends Model
{
    use SoftDeletes;

    protected $table = 'stundenplan_faecher';

    protected $fillable = [
        'kuerzel',
        'name',
        'farbe',
    ];

    /**
     * Einträge dieses Fachs
     */
    public function eintraege(): HasMany
    {
        return $this->hasMany(Eintrag::class, 'fach_id');
    }

    /**
     * Get color for this subject
     */
    public function getColorAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Fallback colors based on subject
        return match(true) {
            str_contains($this->kuerzel, 'MA') => '#3B82F6', // blue
            str_contains($this->kuerzel, 'DE') => '#EAB308', // yellow
            str_contains($this->kuerzel, 'FAwp') => '#A855F7', // purple
            str_contains($this->kuerzel, 'SP') => '#22C55E', // green
            str_contains($this->kuerzel, 'MU') => '#EC4899', // pink
            str_contains($this->kuerzel, 'KU') => '#F97316', // orange
            str_contains($this->kuerzel, 'RE') => '#6366F1', // indigo
            default => '#9CA3AF' // gray
        };
    }
}

