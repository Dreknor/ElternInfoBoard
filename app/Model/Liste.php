<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liste extends Model
{
    use HasFactory;

    protected $table = 'listen';

    protected $fillable = ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple'];

    protected $visible = ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple'];

    protected $casts = [
        'ende' => 'datetime',
        'visible_for_all' => 'boolean',
        'active' => 'boolean',
        'multiple' => 'boolean',
    ];

    public function ersteller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'besitzer');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_listen', 'liste_id');
    }

    public function eintragungen(): HasMany
    {
        return $this->hasMany(Listen_Eintragungen::class, 'listen_id');
    }

    public function termine(): HasMany
    {
        return $this->hasMany(listen_termine::class, 'listen_id');
    }
}
