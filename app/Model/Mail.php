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
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = ['senders_id', 'subject', 'text', 'to', 'file'];

    protected $visible = ['senders_id', 'subject', 'text', 'to', 'file'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senders_id', 'id');
    }

    protected static function booted(): void
    {
        // Mail-Archiv ist personenbezogen: eigene gesendete und an die eigene Adresse
        // gerichtete Mails. Bedingungen geklammert, damit weitere where() greifen.
        static::addGlobalScope('own', function (Builder $builder) {
            $user = auth()->user();

            if ($user === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->where(function (Builder $query) use ($user) {
                $query->where('senders_id', $user->id)
                    ->orWhere('to', $user->email);
            });
        });
    }
}
