<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;


class posts extends Model  implements HasMedia
{
    use HasMediaTrait;

    protected $fillable = ['header', 'news', 'released'];


    protected $dates = ['created_at', 'updated_at'];

    public function groups()
    {
        return $this->belongsToMany(groups::class);
    }
}
