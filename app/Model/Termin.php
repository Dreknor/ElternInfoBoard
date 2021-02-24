<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\CalendarLinks\Link;


class Termin extends Model
{
    protected $table='termine';

    protected $fillable = ['start', 'ende', 'terminname', 'fullDay'];
    protected $visible = ['start', 'ende', 'terminname', 'fullDay'];

    protected $dates = ['creted_at', 'updated_at', 'start', 'ende'];

    protected $casts = [
        'fullDay' => "boolean"
    ];

    public function getfullDayAttribute($value){
        if (is_null($value) or $value = false){
            return false;
        }

        return true;
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_termine');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('date', function (Builder $builder) {
            $builder->where('start', '>=', Carbon::now())
                ->orWhere('ende', '>=', Carbon::now());
        });

        static::created(function () {
            return Cache::forget('termine'.auth()->id());
        });
    }

    public function link(){

        if ($this->fullDay  == 1){
                $ende = $this->ende->addDay();

            return Link::create($this->terminname, $this->start, $ende, $this->fullDay);

        }

        return Link::create($this->terminname, $this->start, $this->ende, $this->fullDay);
    }

}
