<?php

namespace App\Model;

use App\Observers\PostObserver;
use App\Traits\NotificationTrait;
use Artisanry\Commentable\Traits\HasComments;
use Bkwld\Cloner\Cloneable;
use Carbon\Carbon;
use DevDojo\LaravelReactions\Contracts\ReactableInterface;
use DevDojo\LaravelReactions\Traits\Reactable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

#[ObservedBy([PostObserver::class])]
class Post extends Model implements Auditable, HasMedia, ReactableInterface
{
    use Cloneable;
    use HasComments;
    use HasFactory;
    use HasRelationships;
    use InteractsWithMedia;
    use NotificationTrait;
    use \OwenIt\Auditing\Auditable;
    use Reactable;
    use SoftDeletes;

    protected $fillable = ['header', 'news', 'released', 'author', 'archiv_ab', 'type', 'reactable', 'external', 'published_wp_id', 'send_at', 'read_receipt', 'read_receipt_deadline', 'no_header'];

    protected array $cloneable_relations = ['groups', 'rueckmeldung'];

    protected $with = ['rueckmeldung'];

    protected function casts(): array
    {
        return [
            'archiv_ab' => 'datetime',
            'read_receipt_deadline' => 'datetime',
            'reactable' => 'boolean',
            'external' => 'boolean',
            'read_receipt' => 'boolean',
            'no_header' => 'boolean',
        ];
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class);
    }

    public function autor(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'author')->withDefault([
            'name' => config('app.name'),
        ]);
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
        return ! ($this->archiv_ab > Carbon::now());
    }

    #[Scope]
    protected function notArchived(Builder $query): Builder
    {
        return $query->where('archiv_ab', '>', now());
    }

    #[Scope]
    protected function released(Builder $query): Builder
    {
        return $query->where('released', 1);
    }

    public function getSendAttribute(): ?Carbon
    {
        if (! is_null($this->send_at)) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $this->send_at);
        }

        return null;
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(ReadReceipts::class);
    }

    public function userReaction(?User $user = null)
    {
        if (is_null($user)) {
            $user = auth()->user();
        }

        return $this->reactions()
            ->where('responder_id', $user->id)
            ->where('responder_type', get_class($user))->first()?->name;
    }
}
