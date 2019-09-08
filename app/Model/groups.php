<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class groups extends Model
{
    protected $fillable = ['name'];
    protected $visible = ['name'];

    public function users (){
        return $this->belongsToMany(User::class);
    }

    public function posts(){
        return $this->belongsToMany(posts::class);
    }
}
