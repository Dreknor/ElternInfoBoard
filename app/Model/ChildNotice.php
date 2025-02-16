<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChildNotice extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'child_id',
        'notice',
        'date',
        'user_id',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function child()
    {
        return $this->belongsTo(Child::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function scopeFuture(Builder $query)
    {
        return $query->whereDate('date', '>=', today());
    }
}
