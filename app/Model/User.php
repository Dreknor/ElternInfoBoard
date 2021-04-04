<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * Class User
 */
class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use HasPushSubscriptions;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email','publicMail', 'password', 'changePassword', 'benachrichtigung', 'lastEmail', 'sendCopy', 'track_login',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'lastEmail' => 'datetime',
        'changePassword'    => 'boolean',
        'last_online_at'    => 'datetime',
        'track_login'    => 'boolean',
        'changeSettings'    => 'boolean',
    ];

    /**
     * Verknüpfte Gruppen
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }

    /**
     * Posts verknüpft über die Gruppen
     * @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep
     */
    public function posts()
    {
        return $this->hasManyDeep(\App\Model\Post::class, ['group_user', \App\Model\Group::class, 'group_post']);
    }

    /**
     * Posts verknüpft über die Gruppen
     * @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep
     */
    public function postsNotArchived()
    {
        return $this->hasManyDeep(\App\Model\Post::class, ['group_user', \App\Model\Group::class, 'group_post'])->NotArchived();
    }

    /**
     * Eigene Posts
     * @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep
     */
    public function own_posts()
    {
        return $this->hasMany(Post::class, 'author');
    }

    /**
     * Termine Verknüpft über Gruppen
     * @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep
     */
    public function termine()
    {
        return $this->hasManyDeep(\App\Model\Termin::class, ['group_user', \App\Model\Group::class, 'group_termine']);
    }

    /**
     * @return \Staudenmeir\EloquentHasManyDeep\HasManyDeep
     */
    public function listen()
    {
        return $this->hasManyDeep(\App\Model\Liste::class, ['group_user', \App\Model\Group::class, 'group_listen']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function listen_eintragungen()
    {
        return $this->hasMany(listen_termine::class, 'reserviert_fuer');
    }

    //Sorgeberechtigter 2

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function sorgeberechtigter2()
    {
        return $this->hasOne(self::class, 'sorg2');
    }

    /**
     * Check if user has an old password that needs to be reset
     * @return bool
     */
    public function hasOldPassword()
    {
        return $this->changePassword;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userRueckmeldung()
    {
        return $this->hasMany(UserRueckmeldungen::class, 'users_id');
    }

    /**
     * @return mixed
     */
    public function getRueckmeldung()
    {
        $eigeneRueckmeldung = Cache::remember('rueckmeldungen_'.auth()->id(), 60 * 5, function () {
            $eigeneRueckmeldung = $this->userRueckmeldung;
            if (! is_null($this->sorg2)) {
                $sorgRueckmeldung = optional($this->sorgeberechtigter2)->userRueckmeldung;
                if (! is_null($sorgRueckmeldung) and ! is_null($eigeneRueckmeldung)) {
                    return $eigeneRueckmeldung->merge($sorgRueckmeldung);
                } elseif (is_null($eigeneRueckmeldung)) {
                    return $sorgRueckmeldung;
                }
            }
        });

        // Merge collections and return single collection.
        return $eigeneRueckmeldung;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Reinigung()
    {
        return $this->hasMany(Reinigung::class, 'users_id', 'id');
    }

    /**
     * @return mixed|string
     */
    public function getFamilieNameAttribute()
    {
        $Name = explode(' ', $this->name);

        if (count($Name) > 2) {
            $Familienname = '';
            for ($key = 1; $key < count($Name); $key++) {
                $Familienname .= ' '.$Name[$key];
            }

            return $Familienname;
        }

        return $Name[1];
    }

    public function schickzeiten()
    {
        return $this->hasMany(Schickzeiten::class, 'users_id')->orWhere('users_id', $this->sorg2);
    }

    public function schickzeiten_own()
    {
        return $this->hasMany(Schickzeiten::class, 'users_id');
    }

    //Krankmeldungen

    public function krankmeldungen()
    {
        return $this->hasMany(krankmeldungen::class, 'users_id')->orWhere('users_id', $this->sorg2);
    }

    public function comments()
    {
        return $this->morphMany(\Benjivm\Commentable\Models\Comment::class, 'creator');
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class, 'owner');
    }
}
