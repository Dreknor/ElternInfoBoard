<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Liste extends Model
{
    protected $table = "listen";

    protected $fillable= ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple'];

    protected $dates = ['created_at', 'updated_at', 'ende'];

    protected $casts = [
        "visible_for_all"   => 'boolean',
        'active'            => "boolean",
        'multiple'            => "boolean",
    ];

    public function ersteller (){
        return $this->belongsTo(User::class, 'besitzer');
    }

    public function groups () {
        return $this->belongsToMany(Groups::class, 'groups_listen', 'liste_id');
    }

    public function eintragungen () {
        return $this->hasMany(listen_termine::class, 'listen_id');
    }
}
