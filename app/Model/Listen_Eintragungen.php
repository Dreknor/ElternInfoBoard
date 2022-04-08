<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Listen_Eintragungen extends Model
{
    protected $table = 'listen_eintragungen';

    protected $fillable = ['eintragung', 'listen_id', 'user_id', 'created_by'];

    public function liste()
    {
        return $this->belongsTo(Liste::class, 'liste_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeUser($query, $user)
    {
        if ($user != null) {
            return $query->where('user_id', $user);
        }
    }
}
