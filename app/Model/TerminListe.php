<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class TerminListe extends Model
{
    protected $table = "terminListen";

    protected $fillable= ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende'];

    protected $dates = ['created_at', 'updated_at', 'ende'];

    protected $casts = [
        "visible_for_all"   => 'boolean',
        'active'            => "boolean"
    ];

    public function ersteller (){
        return $this->belongsTo(User::class, 'besitzer');
    }

    public function groups () {
        return $this->belongsToMany(Groups::class, 'groups_terminListen');
    }

    public function termine () {
        return $this->hasMany(listen_termine::class);
    }
}
