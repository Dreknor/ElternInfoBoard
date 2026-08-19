<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PflichtstundenFamilyRule extends Model
{
    protected $table = 'pflichtstunden_family_rules';

    protected $fillable = [
        'family_key',
        'period_year',
        'mode',
        'custom_required_hours',
        'reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'custom_required_hours' => 'float',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    public function history(): HasMany
    {
        return $this->hasMany(PflichtstundenFamilyRuleHistory::class, 'pflichtstunden_family_rule_id');
    }
}
