<?php

namespace App\Model;

use Benjivm\Commentable\Traits\HasComments;
use Bkwld\Cloner\Cloneable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

class Post extends Model implements HasMedia
{
    use HasMediaTrait;
    use SoftDeletes;
    use Cloneable;
    use HasComments;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $fillable = ['header', 'news', 'released', 'author', 'archiv_ab', 'type'];

    protected $dates = ['created_at', 'updated_at', 'archiv_ab'];

    protected $cloneable_relations = ['groups', 'rueckmeldung'];

    protected $with = ['rueckmeldung'];

    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    public function autor()
    {
        return $this->hasOne(User::class, 'id', 'author');
    }

    public function rueckmeldung()
    {
        return $this->hasOne(Rueckmeldungen::class);
    }

    public function userRueckmeldung()
    {
        return $this->hasMany(UserRueckmeldungen::class);
    }

    public function users()
    {
        return $this->hasManyDeep('App\Model\User', ['group_post', 'App\Model\Group', 'group_user']);
    }

    public function getIsArchivedAttribute()
    {
        return $this->archiv_ab > Carbon::now() ? false : true;
    }
}
