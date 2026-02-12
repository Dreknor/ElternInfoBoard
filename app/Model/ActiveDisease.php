<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActiveDisease extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['disease_id', 'user_id', 'start', 'end', 'comment', 'active'];

    protected function casts(): array
    {
        return [
            'start' => 'datetime',
            'end' => 'datetime',
        ];
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', 1);
    }
}
