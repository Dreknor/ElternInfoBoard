<?php

namespace App\Model;

use App\Scopes\SortGroupsScope;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Group extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['name', 'bereich', 'protected'];

    protected $visible = ['name', 'bereich', 'protected'];

    protected $casts = [
        'protected' => 'boolean',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new SortGroupsScope());
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    public function termine()
    {
        return $this->belongsToMany(Termin::class, 'group_termine');
    }

    public function listen()
    {
        return $this->belongsToMany(Liste::class, 'group_listen');
    }
}
