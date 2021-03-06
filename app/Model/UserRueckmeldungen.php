<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UserRueckmeldungen extends Model
{
    protected $table = 'users_rueckmeldungen';
    protected $fillable = ['post_id', 'users_id', 'text'];

    public function nachricht()
    {
        return $this->belongsTo(Post::class, 'post_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id');
    }
}
