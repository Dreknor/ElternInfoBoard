<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class ChildCheckIn extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'child_id',
        'checked_in',
        'checked_out',
        'should_be',
        'date',
        'lock_at',
        'comment',
    ];

    protected $casts = [
        'checked_in' => 'boolean',
        'checked_out' => 'boolean',
        'should_be' => 'boolean',
        'date' => 'date',
        'lock_at' => 'date',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function scopeCheckedIn($query)
    {
        return $query->where('checked_in', true)->where('checked_out', false);
    }
}
