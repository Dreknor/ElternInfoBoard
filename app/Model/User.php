<?php

namespace App\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Notifiable;
    use HasRoles;
    use SoftDeletes;

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'changePassword', 'benachrichtigung', 'lastEmail'
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
        'changePassword'    => 'boolean'
    ];

    public function groups(){
        return $this->belongsToMany(Groups::class)->withTimestamps();
    }

    public function posts(){
        return $this->hasManyDeep('App\Model\Posts', ['groups_user', 'App\Model\Groups','groups_posts']);

    }

    /**
     * Check if user has an old password that needs to be reset
     * @return boolean
     */
    public function hasOldPassword()
    {
        return $this->changePassword;
    }

    public function userRueckmeldung(){
        return $this->hasMany(UserRueckmeldungen::class, 'users_id');
    }

    public function rueckmeldungNachricht($postsId){
        return $this->hasMany(UserRueckmeldungen::class, 'users_id')->where('posts_id', $postsId)->first();
    }

}
