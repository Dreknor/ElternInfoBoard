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

    public function child()
    {
        return $this->belongsTo(Child::class);
    }
}
