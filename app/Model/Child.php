<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Attributes\Scope;
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
        'auto_checkIn',
    ];

    protected $casts = [
        'notification' => 'boolean',
        'auto_checkIn' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function mandates()
    {
        return $this->hasMany(ChildMandate::class, 'child_id');
    }

    public function class()
    {
        return $this->belongsTo(Group::class);
    }

    public function parents()
    {
        return $this->belongsToMany(User::class, 'child_user');
    }

    public function arbeitsgemeinschaften()
    {
        return $this->belongsToMany(Arbeitsgemeinschaft::class, 'arbeitsgemeinschaften_participants', 'participant_id', 'ag_id')
            ->where('end_date', '>', now());
    }

    public function arbeitsgemeinschaften_today()
    {
        return Cache::remember('arbeitsgemeinschaften_today_'.$this->id, Carbon::now()->diffInSeconds(Carbon::now()->endOfDay()), function () {
            return $this->arbeitsgemeinschaften()
                ->where('weekday', (now()->dayOfWeek))
                ->where('end_date', '>', now())
                ->where(function ($query) {
                    $query->whereDate('start_date', '<=', today())
                        ->orWhereNull('start_date');
                })
                ->where(function ($query) {
                    $query->whereDate('end_date', '>=', today())
                        ->orWhereNull('end_date');
                })
                ->get();
        });

    }

    public function checkedIn()
    {

        $checkIn = Cache::remember('checkedIn'.$this->id, 300, function () {
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

    /**
     * Determine if the record should be marked as "today".
     *
     * This method checks if there is a relevant "check-in" record for the current
     * instance that matches specific conditions, including not being checked in,
     * not being checked out, being marked as "should be," and having a date of today.
     * The result is cached for 300 seconds to optimize repeated queries.
     *
     * @return bool Returns true if the conditions are met, false otherwise.
     */
    public function should_be_today()
    {
        $checkIn = Cache::remember('should_be_today'.$this->id, 300, function () {
            return $this->checkIns()
                ->where('checked_in', false)
                ->where('checked_out', false)
                ->where('should_be', true)
                ->whereDate('date', today())
                ->first();
        });

        if (is_null($checkIn)) {
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
            ->where(function ($q) {
                $q->where('specific_date', '>=', today())
                    ->orWhereNull('specific_date');
            });
    }

    public function getSchickzeitenForToday()
    {

        $schickzeiten = Cache::remember('schickzeiten_'.$this->id, 300, function () {
            return $this->schickzeiten()
                ->where(function ($query) {
                    /*
                    * alt: Es werden alle Schickzeiten geladen, die entweder heute sind oder für den aktuellen Wochentag gelten.
                    $query->where('weekday', now()->dayOfWeek)
                       ->orWhere('specific_date', today());
                    */
                    // Neu: Es werden alle Schickzeiten geladen, die für heute sind.
                    $query->where('specific_date', today());
                })
                ->orderBy('specific_date', 'desc')
                ->get();
        });

        return $schickzeiten;
        /*
            if ($schickzeiten->where('specific_date',  today())->count() > 0) {
                return $schickzeiten->where('specific_date',  today());
            } else {
                return $schickzeiten->where('weekday', now()->dayOfWeek);
            }
        */
    }

    #[Scope]
    protected function care($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('group_id', (new CareSetting)->groups_list)
                ->orWhereIn('class_id', (new CareSetting)->class_list);
        });
    }

    public function krankmeldungen()
    {
        return $this->hasMany(Krankmeldungen::class, 'child_id')->orderByDesc('created_at');
    }

    public function krankmeldungToday()
    {

        $meldung = Cache::remember('krankmeldung_'.$this->id, Carbon::now()->diffInSeconds(Carbon::now()->endOfDay()), function () {
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
        $notice = Cache::remember('notice'.$this->id, 300, function () {
            return $this->notice()
                ->whereDate('date', today())
                ->first();
        });

        return $notice;
    }

    public function noticeToday()
    {
        $notice = Cache::remember('notice'.$this->id, 300, function () {
            return $this->notice()
                ->whereDate('date', today())
                ->first();
        });

        return $notice;
    }
}
