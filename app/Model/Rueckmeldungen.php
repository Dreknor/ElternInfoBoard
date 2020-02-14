<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Rueckmeldungen extends Model
{
    protected $table = "rueckmeldungen";

    protected $fillable = ['posts_id', 'empfaenger', 'ende', 'text', 'pflicht'];
    protected $visible = ['posts_id', 'empfaenger', 'ende', 'text', 'pflicht'];

    protected $dates = ['created_at', 'updated_at', 'ende'];

    protected $casts = [
        'pflicht' => "boolean"
    ];

    public function post(){
        return $this->belongsTo(Posts::class, 'posts_id');
    }

    public function userRueckmeldungen () {
        return $this->hasMany(UserRueckmeldungen::class, 'posts_id', 'posts_id');
    }



}
