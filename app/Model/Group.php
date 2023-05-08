<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Scopes\SortGroupsScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Group extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;

    protected $fillable = ['name', 'bereich', 'protected'];

    protected $visible = ['name', 'bereich', 'protected'];

    protected $casts = [
        'protected' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SortGroupsScope());
    }

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

    public function listen(): BelongsToMany
    {
        return $this->belongsToMany(Liste::class, 'group_listen');
    }
}
