<?php

namespace App\Model;

use App\Enums\GuardianRelation;
use App\Enums\GuardianRight;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Qualifizierte Beziehung Kind ↔ Bezugsperson (Tabelle child_user).
 *
 * @property int $child_id
 * @property int $user_id
 * @property string|null $relation
 * @property bool $has_custody
 * @property bool $receives_information
 * @property bool $can_manage
 * @property string $source manual|import|ucs|migration
 * @property bool $is_auto_provisioned
 *
 * @see docs/kind-zentriertes-familienmodell-konzept.md §4.1
 */
class ChildGuardian extends Pivot implements Auditable
{
    use \OwenIt\Auditing\Auditable;

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_IMPORT = 'import';

    public const SOURCE_UCS = 'ucs';

    public const SOURCE_MIGRATION = 'migration';

    /** Pivot-Spalten, die an Child::parents() / User::children_rel() mitgeladen werden. */
    public const PIVOT_COLUMNS = [
        'is_auto_provisioned', 'relation', 'synced_at', 'has_custody', 'receives_information',
        'can_manage', 'source', 'valid_until', 'reviewed_at',
    ];

    protected $table = 'child_user';

    public $incrementing = true;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_auto_provisioned' => 'boolean',
            'has_custody' => 'boolean',
            'receives_information' => 'boolean',
            'can_manage' => 'boolean',
            'synced_at' => 'datetime',
            'valid_until' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relationType(): GuardianRelation
    {
        return GuardianRelation::fromInput($this->relation);
    }

    public function grants(GuardianRight $right): bool
    {
        if ($this->valid_until !== null && $this->valid_until->isPast() && ! $this->valid_until->isToday()) {
            return false;
        }

        return (bool) $this->getAttribute($right->column());
    }

    /** Aus der früheren sorg2-Verknüpfung übernommen und noch nicht geprüft (E3). */
    public function isPendingReview(): bool
    {
        return $this->source === self::SOURCE_MIGRATION && $this->reviewed_at === null;
    }

    public function sourceLabel(): string
    {
        return match ($this->source) {
            self::SOURCE_IMPORT => 'Import',
            self::SOURCE_UCS => 'UCS@school',
            self::SOURCE_MIGRATION => 'aus früherer Kontoverknüpfung übernommen',
            default => 'manuell',
        };
    }
}
