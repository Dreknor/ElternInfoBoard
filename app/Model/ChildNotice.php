<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ChildNotice extends Model implements Auditable
{
    use HasFactory;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;

    protected $fillable = [
        'child_id',
        'notice',
        'date',
        'user_id',
    ];

    protected $casts = [
        'date' => 'datetime',
        'created_at' => 'datetime',
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

    public function isNew()
    {
        return $this->updated_at->greaterThanOrEqualTo(now()->subMinutes(20));
    }
}
