<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VertretungsplanNews extends Model
{

    protected $table = 'vertretungsplan_news';
    protected $fillable = ['start', 'ende', 'news'];

    protected $casts = [
        'start' => 'date',
        'ende' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('date', function (Builder $builder) {

            $startDate = Carbon::today();
            $targetDate = Carbon::today()->addDays(3);

            $builder->where(function ($query) use ($targetDate, $startDate) {
                $query->whereDate('start', '<=', $targetDate);
                $query->whereDate('end', '<=', $targetDate);
                $query->whereDate('end', '>=', Carbon::today());
            })
                ->orWhere(function ($query) use ($targetDate, $startDate) {
                    $query->whereDate('start', '<=', $targetDate);
                    $query->whereDate('end', '>=', Carbon::today());
                })
                ->orWhere(function ($query) use ($targetDate) {
                    $query->whereDate('start', '<=', $targetDate);
                    $query->whereNull('end');
                })
                ->orderBy('start');

        });
    }

}
