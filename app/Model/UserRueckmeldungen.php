<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserRueckmeldungen extends Model
{
    use HasFactory;

    protected $table = 'users_rueckmeldungen';

    protected $fillable = ['post_id', 'users_id', 'text', 'rueckmeldung_number'];

    public function nachricht(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id')->withDefault([
            'name' => config('app.name'),
        ]);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AbfrageAntworten::class, 'rueckmeldung_id');
    }
}
