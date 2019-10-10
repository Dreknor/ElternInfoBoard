<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserRueckmeldungen extends Model
{

    protected $table = "users_rueckmeldungen";
    protected $fillable = ['posts_id', 'users_id', 'text'];

    public function nachricht(){
        return $this->belongsTo(Posts::class, 'posts_id', 'id');
    }
}