<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActiveDisease extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['disease_id', 'user_id', 'start', 'end', 'comment', 'active'];

    protected $casts = [
        'start' => 'datetime',
        'end' => 'datetime',
    ];


    public function disease()
    {
        return $this->belongsTo(Disease::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

}
