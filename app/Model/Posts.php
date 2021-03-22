<?php

namespace App\Model;

//use Artisanry\Commentable\Traits\HasComments;
use Benjivm\Commentable\Traits\HasComments;
use Bkwld\Cloner\Cloneable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Posts extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;
    use Cloneable;
    use HasComments;
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;

    protected $fillable = ['header', 'news', 'released', 'author', 'archiv_ab', 'type'];
    protected $casts = [
        'archiv_ab' => 'datetime',
    ];

    protected $cloneable_relations = ['groups', 'rueckmeldung'];

    //protected $with= ['rueckmeldung'];

    public function groups()
    {
        return $this->belongsToMany(Groups::class);
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
        return $this->hasManyDeep(\App\Model\User::class, ['groups_posts', \App\Model\Groups::class, 'groups_user']);
    }

    public function is_archived()
    {
        return $this->archiv_ab > Carbon::now() ? false : true;
    }
}
