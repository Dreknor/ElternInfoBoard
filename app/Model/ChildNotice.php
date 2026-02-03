<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class ChildNotice extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $fillable = [
        'child_id',
        'notice',
        'date',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    #[Scope]
    protected function future(Builder $query)
    {
        return $query->whereDate('date', '>=', today());
    }

    public function isNew()
    {
        return $this->updated_at->greaterThanOrEqualTo(now()->subMinutes(20));
    }
}
