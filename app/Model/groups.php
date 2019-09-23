<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Groups extends Model implements HasMedia
{

    use HasMediaTrait;


    protected $fillable = ['name'];
    protected $visible = ['name'];

    public function users (){
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function posts(){
        return $this->belongsToMany(Posts::class)->withTimestamps();
    }
}
