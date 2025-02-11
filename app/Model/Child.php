<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
        return $this->belongsToMany(User::class);
    }

    public function Schickzeiten()
    {
        return $this->hasMany(Schickzeiten::class, 'child_id');
    }

    public function checkedIn()
    {
        $checkIn = $this->checkIns()
            ->where('checked_in', true)
            ->where('checked_out', false)
            ->whreDate('date', now()->toDateString())
            ->first();

        return $checkIn ? true : false;
    }

    public function checkIns()
    {
        return $this->hasMany(ChildCheckIn::class, 'child_id');
    }


}
