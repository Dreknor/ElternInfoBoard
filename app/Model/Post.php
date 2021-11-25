<?php

namespace App\Model;

use Benjivm\Commentable\Traits\HasComments;
use Bkwld\Cloner\Cloneable;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Contracts\ReactableInterface;
use DevDojo\LaravelReactions\Models\Reaction;
use DevDojo\LaravelReactions\Traits\Reactable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Post extends Model implements HasMedia, ReactableInterface
{
    use InteractsWithMedia;
    use SoftDeletes;
    use Cloneable;
    use HasComments;
    use HasRelationships;
    use Reactable;

    protected $fillable = ['header', 'news', 'released', 'author', 'archiv_ab', 'type', 'reactable'];
    protected $casts = [
        'archiv_ab' => 'datetime',
        'reactable' => 'boolean'
    ];

    protected array $cloneable_relations = ['groups', 'rueckmeldung'];

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
        return $this->hasManyDeep(User::class, ['group_post', Group::class, 'group_user']);
    }

    public function poll()
    {
        return $this->hasOne(Poll::class, 'post_id');
    }

    public function getIsArchivedAttribute()
    {
        return $this->archiv_ab > Carbon::now() ? false : true;
    }

    public function scopeNotArchived($query)
    {
        return $query->where('archiv_ab', '>', now());
    }

    public function scopeReleased($query)
    {
        return $query->where('released', 1);
    }

}
