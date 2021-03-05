<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Groups extends Model implements HasMedia
{
    use HasMediaTrait;

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
        return $this->belongsToMany(Posts::class)->withTimestamps();
    }

    public function termine()
    {
        return $this->belongsToMany(Termin::class);
    }
}
