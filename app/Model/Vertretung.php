<?php

namespace App\Model;

use App\Model\Stundenplan\Klasse;
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
        'klasse_kurzform',
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

    /**
     * Relation zur Gruppe (altes System)
     * Nullable, wenn stattdessen klasse_kurzform verwendet wird
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'klasse');
    }

    /**
     * Relation zur Stundenplan-Klasse (neues System)
     * Nullable, wenn stattdessen klasse (Group) verwendet wird
     */
    public function klasse(): BelongsTo
    {
        return $this->belongsTo(Klasse::class, 'klasse_kurzform', 'kurzform');
    }
}
