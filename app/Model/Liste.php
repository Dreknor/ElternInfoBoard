<?php

namespace App\Model;

use App\Traits\NotificationTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class Liste extends Model
{
    use HasFactory;
    use NotificationTrait;

    protected $table = 'listen';

    protected $fillable = ['listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple', 'make_new_entry'];

    protected $visible = ['id', 'listenname', 'type', 'comment', 'besitzer', 'visible_for_all', 'active', 'ende', 'duration', 'multiple', 'make_new_entry'];

    protected $casts = [
        'ende' => 'datetime',
        'visible_for_all' => 'boolean',
        'active' => 'boolean',
        'multiple' => 'boolean',
        'make_new_entry' => 'boolean',
    ];

    public function ersteller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'besitzer')->withDefault([
            'name' => config('app.name'),
        ]);
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

    public function users(): HasManyDeep
    {
        return $this->hasManyDeep(User::class, ['group_listen', Group::class, 'group_user']);
    }
}
