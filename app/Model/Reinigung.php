<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Reinigung extends Model
{
    protected $table = 'reinigung';
    protected $visible = ['bereich', 'aufgabe', 'datum', 'bemerkung'];
    protected $fillable = ['bereich', 'aufgabe', 'datum', 'bemerkung', 'users_id'];

    protected $dates = ['datum'];

    public function user()
    {
        return $this->belongsTo(User::class, 'users_id', 'id');
    }
}
