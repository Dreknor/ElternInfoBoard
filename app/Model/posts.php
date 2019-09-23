<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;


class Posts extends Model  implements HasMedia
{
    use HasMediaTrait;

    protected $fillable = ['header', 'news', 'released', 'author'];


    protected $dates = ['created_at', 'updated_at'];

    public function groups()
    {
        return $this->belongsToMany(Groups::class);
    }

    public function autor(){
        return $this->hasOne(User::class, 'id', 'author');
    }

    public function rueckmeldung(){
        return $this->hasOne(Rueckmeldungen::class);
    }
}
