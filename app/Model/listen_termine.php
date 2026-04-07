<?php

namespace App\Model;

use App\Observers\ListenTermineObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\CalendarLinks\Link;

#[ObservedBy([ListenTermineObserver::class])]
class listen_termine extends Model
{
    use HasFactory;

    protected $table = 'listen_termine';

    protected $fillable = ['listen_id', 'termin', 'comment', 'reserviert_fuer', 'duration'];

    protected $visible = ['id', 'listen_id', 'termin', 'comment', 'reserviert_fuer', 'duration'];

    protected function casts(): array
    {
        return [
            'termin' => 'datetime',
        ];
    }

    public function eingetragenePerson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reserviert_fuer');
    }

    public function link($Listenname): Link
    {
        return Link::create($Listenname, $this->termin, $this->termin->copy()->addMinutes($this->duration));
    }

    protected function getDurationAttribute($value)
    {
        return ($value != '') ? $value : $this->liste->duration;
    }

    public function liste(): BelongsTo
    {
        return $this->belongsTo(Liste::class, 'listen_id');
    }

    public function pflichtstunde(): HasOne
    {
        return $this->hasOne(Pflichtstunde::class, 'listen_termin_id');
    }

    #[Scope]
    protected function user(Builder $query, $user)
    {
        if ($user != null) {
            return $query->where('reserviert_fuer', $user);
        }
    }

    public function ende(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->termin->addMinutes($this->duration);
            }
        );
    }
}
