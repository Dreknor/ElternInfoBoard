<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schickzeiten extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'schickzeiten';

    protected $fillable = ['users_id', 'child_name', 'weekday', 'specific_date', 'time', 'time_ab','time_spaet','type', 'changedBy', 'child_id',];

    protected $visible = ['child_name', 'weekday', 'specific_date', 'time', 'time_ab','time_spaet', 'type', 'users_id', 'changedBy', 'child_id'];



    protected $casts = [
        'time' => 'datetime:H:i:s',
        'time_ab' => 'datetime:H:i:s',
        'time_spaet' => 'datetime:H:i:s',
        'specific_date' => 'date',
    ];



    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }



    public function getTimeAttribute()
    {
        if ($this->attributes['time']) {
            if (strlen($this->attributes['time']) < 6) {
                $time = Carbon::createFromFormat('H:i', $this->attributes['time']);
            } else {
                $time = Carbon::createFromFormat('H:i:s', $this->attributes['time']);
            }

            return $time;
        }
    }
}
