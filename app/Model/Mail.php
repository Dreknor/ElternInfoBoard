<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Mail extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    protected $fillable = ['senders_id', 'subject', 'text', 'to', 'file'];

    protected $visible = ['senders_id', 'subject', 'text', 'to', 'file'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senders_id', 'id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('own', function (Builder $builder) {

            if (auth()->user()->sorg2 != null){
                $builder->where('senders_id', auth()->id())
                ->orWhere('to', auth()->user()->email)
                ->orWhere('to', auth()->user()->sorg2);
            } else {
                $builder->where('senders_id', auth()->id())
                ->orWhere('to', auth()->user()->email);
            }

        });
    }


}
