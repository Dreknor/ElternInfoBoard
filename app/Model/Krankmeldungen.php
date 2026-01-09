<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Krankmeldungen extends Model implements HasMedia
{
    use SoftDeletes;
    use HasFactory;
    use InteractsWithMedia;

    protected $table = 'krankmeldungen';

    protected $fillable = ['name', 'kommentar', 'start', 'ende', 'users_id', 'child_id', 'disease_id'];

    protected $visible = ['name', 'kommentar', 'start', 'ende', 'users_id', 'child_id', 'disease_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    protected $casts = [
        'start' => 'datetime',
        'ende' => 'datetime',
    ];


    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'disease_id');
    }


    protected static function booted()
    {
        static::saved(function ($krankmeldung) {
            if (!is_null($krankmeldung->child_id)) {
                Cache::forget('krankmeldung_' . $krankmeldung->child_id);
            }
        });
    }

}
