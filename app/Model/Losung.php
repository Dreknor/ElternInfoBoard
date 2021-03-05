<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Losung extends Model
{
    protected $table = 'losungen';

    protected $dates = ['date'];

    public function getDateAttribute($value)
    {
        return ($value != '') ? $value : Carbon::now();
    }
}
