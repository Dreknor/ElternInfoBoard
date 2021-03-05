<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Liste extends Model
{
    protected $table = 'listen';

    protected $fillable = ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple'];

    protected $casts = [
        'ende' => 'datetime',
        'visible_for_all'   => 'boolean',
        'active'            => 'boolean',
        'multiple'            => 'boolean',
    ];

    public function ersteller()
    {
        return $this->belongsTo(User::class, 'besitzer');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_listen', 'liste_id');
    }

    public function eintragungen()
    {
        return $this->hasMany(listen_termine::class, 'listen_id');
    }
}
