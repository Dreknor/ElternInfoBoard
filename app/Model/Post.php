<?php

namespace App\Model;

use Benjivm\Commentable\Traits\HasComments;
use Bkwld\Cloner\Cloneable;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Contracts\ReactableInterface;
use DevDojo\LaravelReactions\Traits\Reactable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Post extends Model implements HasMedia, ReactableInterface
{
    use InteractsWithMedia;
    use SoftDeletes;
    use Cloneable;
    use HasComments;
    use HasRelationships;
    use Reactable;

    protected $fillable = ['header', 'news', 'released', 'author', 'archiv_ab', 'type', 'reactable', 'external','published_wp_id'];

    protected $casts = [
        'archiv_ab' => 'datetime',
        'reactable' => 'boolean',
        'external' => 'boolean',
    ];

    protected array $cloneable_relations = ['groups', 'rueckmeldung'];

    protected $with = ['rueckmeldung'];

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function autor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'author');
    }

    public function rueckmeldung(): HasOne
    {
        return $this->hasOne(Rueckmeldungen::class);
    }

    public function userRueckmeldung(): HasMany
    {
        return $this->hasMany(UserRueckmeldungen::class);
    }

    public function users(): HasManyDeep
    {
        return $this->hasManyDeep(User::class, ['group_post', Group::class, 'group_user']);
    }

    public function poll(): HasOne
    {
        return $this->hasOne(Poll::class, 'post_id');
    }

    public function getIsArchivedAttribute(): bool
    {
        return !($this->archiv_ab > Carbon::now());
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('archiv_ab', '>', now());
    }

    public function scopeReleased(Builder $query): Builder
    {
        return $query->where('released', 1);
    }
}
