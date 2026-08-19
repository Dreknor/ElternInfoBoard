<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Model\User;

class PflichtstundenFamilyRuleHistory extends Model
{
    protected $table = 'pflichtstunden_family_rule_histories';

    protected $fillable = [
        'pflichtstunden_family_rule_id',
        'family_key',
        'period_year',
        'from_mode',
        'to_mode',
        'from_custom_required_hours',
        'to_custom_required_hours',
        'reason',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'from_custom_required_hours' => 'float',
            'to_custom_required_hours' => 'float',
            'changed_by' => 'integer',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo(PflichtstundenFamilyRule::class, 'pflichtstunden_family_rule_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
