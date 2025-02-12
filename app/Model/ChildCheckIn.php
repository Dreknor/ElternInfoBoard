<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildCheckIn extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'checked_in',
        'checked_out',
        'date',
    ];


    protected $casts = [
        'checked_in' => 'boolean',
        'checked_out' => 'boolean',
        'date' => 'date',
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
