<?php

namespace App\Model;

use App\Enums\GuardianRight;
use App\Services\Family\FamilyResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Krankmeldungen extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'krankmeldungen';

    protected $fillable = ['name', 'kommentar', 'start', 'ende', 'users_id', 'child_id', 'disease_id'];

    protected $visible = ['name', 'kommentar', 'start', 'ende', 'users_id', 'child_id', 'disease_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id');
    }

    protected function casts(): array
    {
        return [
            'start'     => 'datetime',
            'ende'      => 'datetime',
            // Datenschutz: Freitext-Kommentar enthält ggf. Gesundheitsdaten (Art. 9 DSGVO)
            // und wird daher AES-verschlüsselt in der DB abgelegt.
            'kommentar' => 'encrypted',
        ];
    }

    /**
     * Krankmeldungen, die $user sehen darf: Meldungen zu Kindern mit Zugriff auf
     * Gesundheitsdaten (Sorgerecht oder Verwaltung), eigene Meldungen sowie
     * Freitext-Meldungen (ohne Kind) der eigenen Familie.
     *
     * @see docs/kind-zentriertes-familienmodell-konzept.md §6.4
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $resolver = app(FamilyResolver::class);
        $childIds = $resolver->childrenQuery($user, GuardianRight::Custody)->pluck('children.id')
            ->merge($resolver->childrenQuery($user, GuardianRight::Manage)->pluck('children.id'))
            ->unique()
            ->values()
            ->all();
        $familyUserIds = $resolver->familyUserIds($user);

        return $query->where(function (Builder $q) use ($user, $childIds, $familyUserIds) {
            $q->where('users_id', $user->id)
                ->orWhereIn('child_id', $childIds)
                ->orWhere(function (Builder $free) use ($familyUserIds) {
                    $free->whereNull('child_id')->whereIn('users_id', $familyUserIds);
                });
        });
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class, 'disease_id');
    }

    protected static function booted()
    {
        static::saved(function ($krankmeldung) {
            if (! is_null($krankmeldung->child_id)) {
                Cache::forget('krankmeldung_'.$krankmeldung->child_id);
            }
        });
    }
}
