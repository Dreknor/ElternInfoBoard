<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PflichtstundenFamilyAccount extends Model
{
    protected $table = 'pflichtstunden_family_accounts';

    protected $fillable = [
        'family_key',
        'period_year',
        'opening_balance_minutes',
        'earned_minutes',
        'required_minutes',
        'closing_balance_minutes',
        'carried_to_next_minutes',
        'carryover_applied',
        'last_calculated_at',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'opening_balance_minutes' => 'integer',
            'earned_minutes' => 'integer',
            'required_minutes' => 'integer',
            'closing_balance_minutes' => 'integer',
            'carried_to_next_minutes' => 'integer',
            'carryover_applied' => 'boolean',
            'last_calculated_at' => 'datetime',
        ];
    }
}
