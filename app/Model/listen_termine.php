<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\CalendarLinks\Link;

class listen_termine extends Model
{
    protected $table = 'listen_termine';

    protected $fillable = ['listen_id', 'termin', 'comment', 'reserviert_fuer'];
    protected $visible = ['listen_id', 'termin', 'comment', 'reserviert_fuer'];
    protected $casts = [
        'termin' => 'datetime',
    ];

    public function eingetragenePerson()
    {
        return $this->belongsTo(User::class, 'reserviert_fuer');
    }

    public function link($Listenname, $duration = 30)
    {
        return Link::create($Listenname, $this->termin, $this->termin->copy()->addMinutes($duration));
    }

    public function liste()
    {
        return $this->belongsTo(Liste::class, 'listen_id');
    }
}
