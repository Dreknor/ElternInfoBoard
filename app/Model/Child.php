<?php

namespace App\Model;

use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Child extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'first_name',
        'last_name',
        'group_id',
        'class_id',
        'notification',
    ];

    protected $casts = [
        'notification' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function class()
    {
        return $this->belongsTo(Group::class);
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'child_user');
    }



    public function checkedIn()
    {

        $checkIn = Cache::remember('checkedIn' . $this->id, 300, function () {
            return $this->checkIns()
                ->where('checked_in', true)
                ->where('checked_out', false)
                ->whereDate('date', today())
                ->first();
        });

        if (is_null($checkIn) or $checkIn->checked_in == false or $checkIn->checked_out == true) {
            return false;
        } else {
            return true;
        }
    }

    public function checkIns()
    {
        return $this->hasMany(ChildCheckIn::class, 'child_id');
    }

    public function schickzeiten(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Schickzeiten::class, 'child_id')
            ->where(function($q) {
                $q->where('specific_date', '>=', today())
                    ->orWhereNull('specific_date');
            });
    }

    public function getSchickzeitenForToday()
    {

        $schickzeiten = $this->schickzeiten()
            ->where(function ($query) {
                $query->where('weekday', now()->dayOfWeek)
                    ->orWhere('specific_date', today());
            })
            ->orderBy('specific_date', 'desc')
            ->get();

        if ($schickzeiten->where('specific_date',  today())->count() > 0) {
            return $schickzeiten->where('specific_date',  today());
        } else {
            return $schickzeiten->where('weekday', now()->dayOfWeek);
        }

    }

    public function scopeCare($query)
    {
        return $query->where(function ($query) {
           $query->whereIn('group_id', (new CareSetting())->groups_list)
                ->orWhereIn('class_id', (new CareSetting())->class_list);
        });
    }

    public function krankmeldungen()
    {
        return $this->hasMany(krankmeldungen::class, 'child_id')->orderByDesc('created_at');
    }

    public function krankmeldungToday()
    {

         $meldung = Cache::remember('krankmeldung_'.$this->id, Carbon::now()->diffInSeconds(Carbon::now()->endOfDay()), function(){
             return $this->krankmeldungen()
            ->where(function ($query) {
                $query->whereDate('start', '<=', today())
                    ->whereDate('ende', '>=', today());
            })
            ->get();
            });

         if ($meldung->count() > 0) {
             return true;
            } else {
                return false;
            }
    }

    public function notice()
    {
        return $this->hasMany(ChildNotice::class, 'child_id')->orderByDesc('created_at');
    }


    public function hasNotice()
    {
        $notice = Cache::remember('notice' . $this->id, 300, function () {
            return $this->notice()
                ->whereDate('date', today())
                ->first();
        });

        return $notice;
    }
}
