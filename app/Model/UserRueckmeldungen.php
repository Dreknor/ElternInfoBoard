<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserRueckmeldungen extends Model
{

    protected $table = "users_rueckmeldungen";
    protected $fillable = ['posts_id', 'users_id', 'text'];
}