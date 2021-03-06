<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Losung extends Model
{
    protected $table = 'losungen';
    protected $casts = [
        'date' => 'datetime',
    ];

    public function getDateAttribute($value)
    {
        return ($value != '') ? $value : Carbon::now();
    }
}
