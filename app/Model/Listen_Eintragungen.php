<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listen_Eintragungen extends Model
{
    use HasFactory;

    protected $table = 'listen_eintragungen';

    protected $fillable = ['eintragung', 'listen_id', 'user_id', 'created_by'];

    public function liste(): BelongsTo
    {
        return $this->belongsTo(Liste::class, 'listen_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUser(Builder $query, $user): null|Builder
    {
        if ($user != null) {
            return $query->where('user_id', $user);
        }

        return null;
    }
}
