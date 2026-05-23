<?php

namespace App\Model;

use App\Scopes\GetGroupsScope;
use App\Scopes\SortGroupsScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ScopedBy([GetGroupsScope::class])]
#[ScopedBy([SortGroupsScope::class])]
class Group extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = ['name', 'bereich', 'protected', 'owner_id', 'has_chat', 'ucs_class_url', 'ucs_source', 'ucs_synced_at'];

    protected $visible = ['name', 'bereich', 'protected', 'owner_id', 'has_chat'];

    protected function casts(): array
    {
        return [
            'protected' => 'boolean',
            'has_chat'  => 'boolean',
            'ucs_synced_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    public function termine(): BelongsToMany
    {
        return $this->belongsToMany(Termin::class, 'group_termine');
    }

    public function listen(): BelongsToMany
    {
        return $this->belongsToMany(Liste::class, 'group_listen');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function vertretungen(): HasMany
    {
        return $this->hasMany(Vertretung::class, 'klasse');
    }

    public function arbeitsgemeinschaften(): BelongsToMany
    {
        return $this->belongsToMany(Arbeitsgemeinschaft::class, 'arbeitsgemeinschaften_groups', 'group_id', 'ag_id');
    }

    public function conversation(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Conversation::class)->withoutGlobalScopes();
    }

    // ── UCS-Scopes ────────────────────────────────────────────────────────────

    /**
     * Nur Gruppen/Klassen, die aus UCS@school stammen (ucs_source = 'kelvin').
     *
     * @see docs/ucs-kelvin-integration-konzept.md §4.2
     */
    public function scopeFromUcs($query)
    {
        return $query->where('ucs_source', 'kelvin');
    }

    /**
     * Nur lokal verwaltete Gruppen (ucs_source = 'local').
     *
     * @see docs/ucs-kelvin-integration-konzept.md §4.2
     */
    public function scopeLocal($query)
    {
        return $query->where('ucs_source', 'local');
    }
}
