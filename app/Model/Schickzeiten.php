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

    protected $fillable = ['users_id', 'child_name', 'weekday', 'time', 'type', 'changedBy'];

    protected $visible = ['child_name', 'weekday', 'time', 'type', 'users_id', 'changedBy'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
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
