<?php

namespace App\Model;

use App\Enums\InquiryStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

/**
 * Eine konkrete Abfrage auf Basis einer freigegebenen Vorlagenversion.
 *
 * Nach dem Start dürfen Inhalte (Titel, Einführung, Vorlage, Zielgruppe) nicht mehr
 * stillschweigend geändert werden – nur Frist, Benachrichtigungs- und Erinnerungs-
 * einstellungen sowie die verantwortliche Person; jede Änderung wird protokolliert.
 */
class Inquiry extends Model
{
    use SoftDeletes;

    public const TARGET_ALL = 'all';

    public const TARGET_GROUPS = 'groups';

    /** Inhalte, die nach der Freigabe gesperrt sind. */
    public const LOCKED_AFTER_APPROVAL = ['template_version_id', 'title', 'intro', 'target_type', 'target_group_ids'];

    protected $fillable = [
        'template_version_id', 'title', 'intro', 'status', 'starts_at', 'deadline_at', 'target_type',
        'target_group_ids', 'responsible_user_id', 'created_by', 'notify_mail', 'notify_push',
        'auto_reminders', 'staff_visible', 'correction_window_days', 'retention_days', 'review_note',
        'submitted_at', 'approved_by', 'approved_at', 'expected_children', 'expected_accounts',
        'released_at', 'closed_at', 'purged_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InquiryStatus::class,
            'starts_at' => 'datetime',
            'deadline_at' => 'datetime',
            'target_group_ids' => 'array',
            'notify_mail' => 'boolean',
            'notify_push' => 'boolean',
            'auto_reminders' => 'boolean',
            'staff_visible' => 'boolean',
            'correction_window_days' => 'integer',
            'retention_days' => 'integer',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
            'closed_at' => 'datetime',
            'purged_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Inquiry $inquiry) {
            $original = InquiryStatus::tryFrom((string) $inquiry->getRawOriginal('status'));
            if (in_array($original, [InquiryStatus::Scheduled, InquiryStatus::Running, InquiryStatus::Closed], true)
                && $inquiry->isDirty(self::LOCKED_AFTER_APPROVAL)) {
                throw new LogicException('Eine freigegebene Abfrage darf inhaltlich nicht verändert werden.');
            }
        });
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(InquiryTemplateVersion::class, 'template_version_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(InquiryAssignment::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InquiryResponse::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(InquiryEvent::class)->orderByDesc('id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InquiryDelivery::class);
    }

    public function exports(): HasMany
    {
        return $this->hasMany(InquiryExport::class)->orderByDesc('id');
    }

    public function responsible(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isEditable(): bool
    {
        return $this->status === InquiryStatus::Draft;
    }

    public function isRunning(): bool
    {
        return $this->status === InquiryStatus::Running;
    }

    public function isReleased(): bool
    {
        return $this->status->isReleased();
    }

    public function deadlinePassed(?CarbonInterface $now = null): bool
    {
        return ($now ?? now())->greaterThan($this->deadline_at);
    }

    /** Gruppen der Zielgruppe (ohne Sichtbarkeits-Scope). */
    public function targetGroups()
    {
        return Group::withoutGlobalScopes()->whereIn('id', $this->target_group_ids ?? [])->orderBy('name')->get();
    }

    public function targetLabel(): string
    {
        if ($this->target_type === self::TARGET_ALL) {
            return 'Gesamte Schule';
        }

        return $this->targetGroups()->pluck('name')->implode(', ') ?: '–';
    }

    /** Für Mitarbeitende sichtbar und noch nicht gelöscht (Zweckbindung). */
    public function scopeStaffVisible($query)
    {
        return $query->where('staff_visible', true)->whereNull('purged_at');
    }

    public function assertStatus(InquiryStatus ...$allowed): void
    {
        if (! in_array($this->status, $allowed, true)) {
            abort(409, 'Diese Aktion ist im Status „'.$this->status->label().'“ nicht möglich.');
        }
    }
}
