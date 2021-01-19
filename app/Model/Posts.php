<?php

namespace App\Model;

use Artisanry\Commentable\Traits\HasComments;
use Bkwld\Cloner\Cloneable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\MediaLibrary\Models\Media;


class Posts extends Model  implements HasMedia
{
    use HasMediaTrait;
    use SoftDeletes;
    use Cloneable;
    use HasComments;

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;


    protected $fillable = ['header', 'news', 'released', 'author', 'archiv_ab', 'type'];

    protected $dates = ['created_at', 'updated_at', 'archiv_ab'];

    protected $cloneable_relations = ['groups', 'rueckmeldung'];


    protected $with= ['rueckmeldung'];

    public function groups()
    {
        return $this->belongsToMany(Groups::class);
    }

    public function autor(){
        return $this->hasOne(User::class, 'id', 'author');
    }

    public function rueckmeldung(){
        return $this->hasOne(Rueckmeldungen::class);
    }


    public function userRueckmeldung(){
        return $this->hasMany(UserRueckmeldungen::class);
    }

    public function users(){
        return $this->hasManyDeep('App\Model\User', ['groups_posts', 'App\Model\Groups','groups_user']);
    }

    public function is_archived(){
        return $this->archiv_ab > Carbon::now() ? false : true;
    }

}
