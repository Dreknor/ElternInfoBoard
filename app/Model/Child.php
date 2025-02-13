<?php

namespace App\Model;

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


    public function getSchickzeiten()
    {
        return $this->hasMany(Schickzeiten::class, 'child_id');
    }

    public function checkedIn()
    {

        $checkIn = Cache::remember('checkedIn' . $this->id, 300, function () {
            return $this->checkIns()
                ->where('checked_in', true)
                ->where('checked_out', false)
                ->whereDate('date', now()->toDateString())
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
        return $this->hasMany(Schickzeiten::class, 'child_id');
    }

    public function getSchickzeitenForToday()
    {
        return $this->schickzeiten()
            ->where(function ($query) {
                $query->where('weekday', now()->format('l'))
                    ->orWhere('specific_date', now()->toDateString());

            })
            ->get();
    }
}
