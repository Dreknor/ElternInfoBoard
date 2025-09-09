<?php

namespace App\Model;

use App\Observers\PflichtstundenObserver;
use App\Settings\PflichtstundenSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([PflichtstundenObserver::class])]
class Pflichtstunde extends Model
{
    use SoftDeletes;

    protected $table = 'pflichtstunden';

    protected $fillable = [
        'user_id',
        'start',
        'end',
        'description',
        'approved',
        'approved_at',
        'approved_by',
        'rejected',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts =[
        'start' => 'datetime',
        'end' => 'datetime',
        'approved' => 'boolean',
        'approved_at' => 'datetime',
        'rejected' => 'boolean',
        'rejected_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function getDurationAttribute()
    {
        return $this->start->diffInMinutes($this->end);
    }

    protected static function booted(): void

    {
        static::addGlobalScope('aktuellerZeitraum', function ($query) {
            $setting = (new PflichtstundenSetting());
            $start = Carbon::createFromFormat('m-d', $setting->pflichtstunden_start)->startOfDay();
            if ($start->isFuture()) {
                $start->subYear();
            }
            $end = Carbon::createFromFormat('m-d', $setting->pflichtstunden_ende)->endOfDay();
            if ($end->isPast()) {
                $end->addYear();
            }
            $query->whereBetween('start', [$start, $end]);


        });
    }
}
