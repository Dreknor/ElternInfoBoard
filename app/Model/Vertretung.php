<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

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
