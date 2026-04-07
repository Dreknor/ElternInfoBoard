<?php

namespace App\Model;

use Carbon\Carbon;
use DevDojo\LaravelReactions\Traits\Reacts;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

/**
 * Class User
 */
class User extends Authenticatable implements Auditable
{
    use HasApiTokens;
    use HasFactory;
    use HasPushSubscriptions;
    use HasRelationships;
    use HasRoles;
    use Notifiable;
    use \OwenIt\Auditing\Auditable;
    use Reacts;
    use SoftDeletes;

    // fill uuid column
    protected static function booted(): void
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
        'is_active', 'deactivated_at',
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'lastEmail' => 'datetime',
            'changePassword' => 'boolean',
            'last_online_at' => 'datetime',
            'track_login' => 'boolean',
            'changeSettings' => 'boolean',
            'is_active' => 'boolean',        // TODO-2.5
            'deactivated_at' => 'datetime',  // TODO-2.5
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    protected function lastEmail(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => Carbon::createFromFormat('Y-m-d H:i:s', ($value != null) ? $value : $this->created_at),
        );
    }

    public function files(): Collection
    {
        return $this->groups()->with('media')->get()->pluck('media')->unique('file_name')->sortBy('file_name')->flatten();
    }

    /**
     * Verknüpfte Kinder
     */
    public function children_rel(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_user');

    }

    /**
     * @return mixed
     */
    public function children()
    {
        $children = $this->children_rel;
        if (! is_null($this->sorg2)) {
            $children2 = $this->sorgeberechtigter2?->children_rel;
            if (! is_null($children2) and ! is_null($children)) {
                return $children->merge($children2);
            } elseif (is_null($children)) {
                return $children2;
            }
        }

        return $children;
    }

    /**
     * Verknüpfte Gruppen
     */
    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)->withTimestamps();
    }

    public function ownGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'owner_id');
    }

    /**
     * Posts verknüpft über die Gruppen
     */
    public function posts(): HasManyDeep
    {
        return $this->hasManyDeep(Post::class, ['group_user', Group::class, 'group_post']);
    }

    public function sites(): HasManyDeep
    {
        return $this->hasManyDeep(Site::class, ['group_user', Group::class, 'site_group']);
    }

    public function vertretungen(): HasManyDeep
    {
        return $this->hasManyDeep(Vertretung::class, ['group_user', Group::class], ['user_id', 'id', 'klasse']);
    }

    /**
     * Posts verknüpft über die Gruppen
     */
    public function postsNotArchived(): HasManyDeep
    {
        return $this->hasManyDeep(Post::class, ['group_user', Group::class, 'group_post'])
            ->where('archiv_ab', '>', now());
    }

    /**
     * Eigene Posts
     */
    public function own_posts(): HasMany
    {
        return $this->hasMany(Post::class, 'author');
    }

    /**
     * Termine Verknüpft über Gruppen
     */
    public function termine(): HasManyDeep
    {
        return $this->hasManyDeep(Termin::class, ['group_user', Group::class, 'group_termine']);
    }

    public function listen(): HasManyDeep
    {
        return $this->hasManyDeep(Liste::class, ['group_user', Group::class, 'group_listen']);
    }

    public function listen_termine(): HasMany
    {
        return $this->hasMany(listen_termine::class, 'reserviert_fuer');
    }

    public function getListenTermine()
    {
        $eigeneEintragungen = $this->listen_termine;

        if (! is_null($this->sorg2)) {
            $sorgEintragung = $this->sorgeberechtigter2?->listen_termine;
            if (! is_null($sorgEintragung) and ! is_null($eigeneEintragungen)) {
                return $eigeneEintragungen->merge($sorgEintragung);
            } elseif (is_null($eigeneEintragungen)) {
                return $sorgEintragung;
            }
        }

        // Merge collections and return single collection.
        return $eigeneEintragungen;
    }

    // Sorgeberechtigter 2

    public function sorgeberechtigter2(): HasOne
    {
        return $this->hasOne(self::class, 'sorg2');
    }

    /**
     * Check if user has an old password that needs to be reset
     */
    public function hasOldPassword(): bool
    {
        return $this->changePassword;
    }

    public function userRueckmeldung(): HasMany
    {
        return $this->hasMany(UserRueckmeldungen::class, 'users_id');
    }

    public function getRueckmeldung(): mixed
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

    public function Reinigung(): HasMany
    {
        return $this->hasMany(Reinigung::class, 'users_id', 'id');
    }

    /**
     * @return mixed|string
     */
    public function getFamilieNameAttribute(): mixed
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

    public function schickzeiten(): HasMany
    {
        return $this->hasMany(Schickzeiten::class, 'users_id')->orWhere('users_id', $this->sorg2);
    }

    public function schickzeiten_own(): HasMany
    {
        return $this->hasMany(Schickzeiten::class, 'users_id');
    }

    // Krankmeldungen

    public function krankmeldungen(): HasMany
    {
        return $this->hasMany(Krankmeldungen::class, 'users_id')->orWhere('users_id', $this->sorg2)->orderByDesc('created_at');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Comment::class, 'creator');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class, 'owner');
    }

    public function mails(): HasMany
    {
        return $this->hasMany(Mail::class, 'senders_id')->orderByDesc('created_at');
    }

    public function read_receipts(): HasMany
    {
        return $this->hasMany(ReadReceipts::class, 'user_id');
    }

    public function pollVotes(): HasMany
    {
        return $this->hasMany(Poll_Votes::class, 'author_id');
    }

    public function pflichtstunden(): HasMany
    {
        if ($this->sorg2 != null) {
            return $this->hasMany(Pflichtstunde::class, 'user_id')->orWhere('user_id', $this->sorg2);
        }

        return $this->hasMany(Pflichtstunde::class, 'user_id');
    }
}
