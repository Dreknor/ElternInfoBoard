<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function group()
    {
        return $this->belongsTo(Group::class, 'klasse');
    }
}
