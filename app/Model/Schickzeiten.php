<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schickzeiten extends Model
{
    use SoftDeletes;
    protected $table = "schickzeiten";

    protected $fillable = ['users_id', 'child_name', 'weekday', 'time', 'type'];
    protected $visible = [ 'child_name', 'weekday', 'time', 'type', 'users_id'];


    public function user(){
        return $this->belongsTo(User::class, 'users_id');
    }

    public function getTimeAttribute()
    {

        if ($this->attributes['time']){
            if (strlen($this->attributes['time']) < 6){
                $time = Carbon::createFromFormat('H:i', $this->attributes['time']);
            } else {
                $time = Carbon::createFromFormat('H:i:s', $this->attributes['time']);
            }

            return $time;
        }

    }
}
