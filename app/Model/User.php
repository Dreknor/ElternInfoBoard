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
    //use SoftDeletes;

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'changePassword', 'benachrichtigung', 'lastEmail', 'sendCopy'
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
        'changePassword'    => 'boolean'
    ];


    public function groups(){
        return $this->belongsToMany(Groups::class)->withTimestamps();
    }

    public function posts(){
        return $this->hasManyDeep('App\Model\Posts', ['groups_user', 'App\Model\Groups','groups_posts']);

    }

    //Sorgeberechtigter 2

    public function sorgeberechtigter2(){
        return $this->hasOne(User::class, 'sorg2');
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

    public function Reinigung(){
        return $this->hasMany(Reinigung::class, 'users_id', 'id');
    }


    public function getFamilieNameAttribute(){
        $Name = explode(' ', $this->name);

        if (count($Name) > 2){
            $Familienname = "";
            for ($key=1; $key < count($Name); $key++){
                $Familienname.= " ".$Name[$key];
            }
            return $Familienname;
        }
        return $Name[1];
    }
}
