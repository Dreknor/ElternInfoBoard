<?php

namespace App\Model;

use App\Observers\VertretungObserver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([VertretungObserver::class])]
class Vertretung extends Model
{
    protected $table = 'vertretungen';

    protected $fillable = [
        'id',
        'date',
        'klasse',
        'stunde',
        'altFach',
        'neuFach',
        'lehrer',
        'comment',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('date', function (Builder $builder) {
            $builder->where('date', '>', Carbon::now()->subDay());
        });

    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'klasse');
    }
}
