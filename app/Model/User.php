<?php

namespace App\Model;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use DevDojo\LaravelReactions\Traits\Reacts;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class User
 */
class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasRoles;
    use HasPushSubscriptions;
    use HasRelationships;
    use Reacts;
    use HasApiTokens;

    //fill uuid column
    protected static function booted()
    {
        parent::boot();
        static::creating(fn ($foo) => $foo->uuid = Str::uuid());
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'publicMail', 'publicPhone', 'sorg2', 'password', 'changePassword', 'benachrichtigung', 'lastEmail', 'sendCopy', 'track_login', 'uuid', 'releaseCalendar', 'calendar_prefix', 'changeSettings',
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
        'changePassword' => 'boolean',
        'last_online_at' => 'datetime',
        'track_login' => 'boolean',
        'changeSettings' => 'boolean',
    ];

    protected function lastEmail(): Attribute {
        return Attribute::make(
            get: fn ($value) => Carbon::createFromFormat('Y-m-d H:i:s', ($value != null)? $value : $this->created_at),
        );
    }

    /**
     * Verknüpfte Gruppen
     *
     * @return BelongsToMany
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }

    /**
     * @return HasMany
     */
    public function ownGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Posts verknüpft über die Gruppen
     *
     * @return HasManyDeep
     */
    public function posts()
    {
        return $this->hasManyDeep(Post::class, ['group_user', Group::class, 'group_post']);
    }

    /**
     * Posts verknüpft über die Gruppen
     *
     * @return HasManyDeep
     */
    public function postsNotArchived()
    {
        return $this->hasManyDeep(Post::class, ['group_user', Group::class, 'group_post'])->NotArchived();
    }

    /**
     * Eigene Posts
     *
     * @return HasMany
     */
    public function own_posts()
    {
        return $this->hasMany(Post::class, 'author');
    }

    /**
     * Termine Verknüpft über Gruppen
     *
     * @return HasManyDeep
     */
    public function termine()
    {
        return $this->hasManyDeep(Termin::class, ['group_user', Group::class, 'group_termine']);
    }

    /**
     * @return HasManyDeep
     */
    public function listen()
    {
        return $this->hasManyDeep(Liste::class, ['group_user', Group::class, 'group_listen']);
    }

    /**
     * @return HasMany
     */
    public function listen_termine()
    {
        return $this->hasMany(listen_termine::class, 'reserviert_fuer');
    }

    public function getListenTermine()
    {
        $eigeneEintragungen = $this->listen_termine;

        if (!is_null($this->sorg2)) {
            $sorgEintragung = $this->sorgeberechtigter2?->listen_termine;
            if (!is_null($sorgEintragung) and !is_null($eigeneEintragungen)) {
                return $eigeneEintragungen->merge($sorgEintragung);
            } elseif (is_null($eigeneEintragungen)) {
                return $sorgEintragung;
            }
        }

        // Merge collections and return single collection.
        return $eigeneEintragungen;
    }

    //Sorgeberechtigter 2

    /**
     * @return HasOne
     */
    public function sorgeberechtigter2()
    {
        return $this->hasOne(self::class, 'sorg2');
    }

    /**
     * Check if user has an old password that needs to be reset
     *
     * @return bool
     */
    public function hasOldPassword()
    {
        return $this->changePassword;
    }

    /**
     * @return HasMany
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
        $eigeneRueckmeldung = $this->userRueckmeldung;

        if (! is_null($this->sorg2)) {
            $sorgRueckmeldung = $this->sorgeberechtigter2?->userRueckmeldung;
            if (! is_null($sorgRueckmeldung) and ! is_null($eigeneRueckmeldung)) {
                return $eigeneRueckmeldung->merge($sorgRueckmeldung);
            } elseif (is_null($eigeneRueckmeldung)) {
                return $sorgRueckmeldung;
            }
        }

        // Merge collections and return single collection.
        return $eigeneRueckmeldung;
    }

    /**
     * @return HasMany
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

        if (array_key_exists(1, $Name)) {
            return $Name[1];
        }

        return Str::of($this->name)->trim();
    }


    public function getVornameAttribute()
    {

        $vorname = Str::before($this->name, ' ');
        return $vorname;
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
        return $this->hasMany(krankmeldungen::class, 'users_id')->orWhere('users_id', $this->sorg2)->orderByDesc('created_at');
    }

    public function comments()
    {
        return $this->morphMany(\Benjivm\Commentable\Models\Comment::class, 'creator');
    }

    public function discussions()
    {
        return $this->hasMany(Discussion::class, 'owner');
    }

    public function mails()
    {
        return $this->hasMany(Mail::class, 'senders_id')->orderByDesc('created_at');
    }

    public function read_receipts()
    {
        return $this->hasMany(ReadReceipts::class, 'user_id');
    }
}
