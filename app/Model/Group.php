<?php

namespace App\Model;

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
        return $this->belongsToMany(Termin::class);
    }
    public function listen()
    {
        return $this->belongsToMany(Liste::class, 'group_listen');
    }
}
