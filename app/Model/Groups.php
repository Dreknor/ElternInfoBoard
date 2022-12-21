<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Groups extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name', 'bereich', 'protected'];

    protected $visible = ['name', 'bereich', 'protected'];

    protected $casts = [
        'protected' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    public function termine(): BelongsToMany
    {
        return $this->belongsToMany(Termin::class, 'group_termine');
    }
}
