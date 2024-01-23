<?php

namespace App\Model;

use App\Traits\NotificationTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;
use Spatie\CalendarLinks\Link;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class Termin extends Model
{
    use HasFactory;
    use NotificationTrait;

    protected $table = 'termine';

    protected $fillable = ['start', 'ende', 'terminname', 'fullDay', 'public'];

    protected $visible = ['start', 'ende', 'terminname', 'fullDay', 'public', 'id'];

    protected $casts = [
        'creted_at' => 'datetime',
        'start' => 'datetime',
        'ende' => 'datetime',
        'fullDay' => 'boolean',
        'public' => 'boolean',
    ];

    public function getfullDayAttribute($value): bool
    {
        if (is_null($value) or $value = false) {
            return false;
        }

        return true;
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_termine');
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('date', function (Builder $builder) {
            $builder->whereDate('start', '>=', Carbon::yesterday()->startOfDay())
                ->orWhereDate('ende', '>=', Carbon::tomorrow()->startOfDay());
        });

        static::created(function () {
            return Cache::forget('termine'.auth()->id());
        });
    }

    public function link($prefix = null): Link
    {
        if (!is_null($prefix)) {
            $terminname = '(' . $prefix . ') ' . $this->terminname;
        } else {
            $terminname = $this->terminname;
        }

        if ($this->fullDay == 1) {
            $ende = $this->ende->addDay();

            return Link::create($terminname, $this->start, $ende, $this->fullDay);
        }

        return Link::create($terminname, $this->start, $this->ende, $this->fullDay);
    }

    public function users(): HasManyDeep
    {
        return $this->hasManyDeep(User::class, ['group_termine', Group::class, 'group_user']);
    }

}
