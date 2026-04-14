<?php

namespace App\Model;

use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    protected function casts(): array
    {
        return [
            'notification' => 'boolean',
            'auto_checkIn' => 'boolean',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function mandates(): HasMany
    {
        return $this->hasMany(ChildMandate::class, 'child_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'child_user');
    }

    public function arbeitsgemeinschaften(): BelongsToMany
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


    /**
     * Check if the child is currently checked in today.
     */
    public function checkedIn(): bool
    {
        // Wenn die Beziehung bereits geladen ist, verwende sie
        if ($this->relationLoaded('checkIns')) {
            $checkIn = $this->checkIns
                ->where('checked_in', true)
                ->where('checked_out', false)
                ->filter(function ($item) {
                    return $item->date->isToday();
                })
                ->first();
            return !is_null($checkIn);
        }

        // Fallback mit Cache für direkte Aufrufe
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
        // Wenn die Beziehung bereits geladen ist, verwende sie
        if ($this->relationLoaded('checkIns')) {
            $checkIn = $this->checkIns
                ->where('checked_in', false)
                ->where('checked_out', false)
                ->where('should_be', true)
                ->filter(function ($item) {
                    return $item->date->isToday();
                })
                ->first();
            return !is_null($checkIn);
        }

        // Fallback mit Cache für direkte Aufrufe
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

    public function checkIns(): HasMany
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
        // Wenn die Beziehung bereits geladen ist, verwende sie
        if ($this->relationLoaded('schickzeiten')) {
            return $this->schickzeiten;
        }

        // Fallback mit Cache für direkte Aufrufe
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

    public function scopeCare($query)
    {
        $careSettings = new CareSetting;

        if (empty($careSettings->groups_list) && empty($careSettings->class_list)) {
            return $query;
        }

        return $query->whereIn('group_id', $careSettings->groups_list)
            ->whereIn('class_id', $careSettings->class_list);
    }

    public function krankmeldungen(): HasMany
    {
        return $this->hasMany(Krankmeldungen::class, 'child_id')->orderByDesc('created_at');
    }

    public function krankmeldungToday()
    {
        // Wenn die Beziehung bereits geladen ist, verwende sie
        if ($this->relationLoaded('krankmeldungen')) {
            return $this->krankmeldungen->count() > 0;
        }

        // Fallback mit Cache für direkte Aufrufe
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

    public function notice(): HasMany
    {
        return $this->hasMany(ChildNotice::class, 'child_id')->orderByDesc('created_at');
    }

    public function hasNotice()
    {
        // Wenn die Beziehung bereits geladen ist, verwende sie
        if ($this->relationLoaded('notice')) {
            return $this->notice->first();
        }

        // Fallback mit Cache für direkte Aufrufe
        $notice = Cache::remember('notice'.$this->id, 300, function () {
            return $this->notice()
                ->whereDate('date', today())
                ->first();
        });

        return $notice;
    }

    public function noticeToday()
    {
        // Wenn die Beziehung bereits geladen ist, verwende sie
        if ($this->relationLoaded('notice')) {
            return $this->notice->first();
        }

        // Fallback mit Cache für direkte Aufrufe
        $notice = Cache::remember('notice'.$this->id, 300, function () {
            return $this->notice()
                ->whereDate('date', today())
                ->first();
        });

        return $notice;
    }
}
