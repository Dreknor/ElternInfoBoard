<?php

namespace App\Model;

use App\Enums\InquiryFieldType;
use App\Enums\InquiryTemplateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

/**
 * Versionierte Vorlage: Zweck, Elterninformation, zuständige Stelle, Fristvorgabe,
 * Aufbewahrung und Formularfelder. Nach der Freigabe unveränderlich.
 *
 * @property array<int, array<string, mixed>> $fields
 */
class InquiryTemplateVersion extends Model
{
    /** Spalten, die sich nach der Freigabe noch ändern dürfen (Statuswechsel). */
    private const MUTABLE_AFTER_APPROVAL = ['status', 'updated_at'];

    protected $fillable = [
        'template_id', 'version', 'status', 'title', 'purpose', 'parent_info', 'responsible_office',
        'target_hint', 'default_deadline_days', 'retention_days', 'retention_unlimited', 'retention_note', 'is_sensitive', 'requires_all_guardians', 'record_relevant',
        'fields', 'review_note', 'created_by', 'submitted_at', 'reviewed_by', 'reviewed_at',
        'privacy_checked_by', 'privacy_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InquiryTemplateStatus::class,
            'fields' => 'json:unicode',
            'is_sensitive' => 'boolean',
            'requires_all_guardians' => 'boolean',
            'record_relevant' => 'boolean',
            'retention_unlimited' => 'boolean',
            'default_deadline_days' => 'integer',
            'retention_days' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'privacy_checked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (InquiryTemplateVersion $version) {
            $original = InquiryTemplateStatus::tryFrom((string) $version->getRawOriginal('status'));
            if (in_array($original, [InquiryTemplateStatus::Approved, InquiryTemplateStatus::Superseded], true)
                && array_diff(array_keys($version->getDirty()), self::MUTABLE_AFTER_APPROVAL) !== []) {
                throw new LogicException('Freigegebene Vorlagenversionen sind unveränderlich. Bitte eine neue Version anlegen.');
            }
        });
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(InquiryTemplate::class, 'template_id')->withTrashed();
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class, 'template_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function privacyChecker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'privacy_checked_by');
    }

    /** Eingabefelder (ohne Abschnitte/Hinweise). */
    public function inputFields(): array
    {
        return array_values(array_filter(
            $this->fields ?? [],
            fn (array $field) => InquiryFieldType::tryFrom($field['type'] ?? '')?->isInput() ?? false
        ));
    }

    public function retentionLabel(): string
    {
        return match (true) {
            $this->retention_unlimited => 'unbefristet (manuelle Löschung)',
            $this->retention_days !== null => $this->retention_days.' Tage nach Abschluss',
            default => 'Standard',
        };
    }

    public function label(): string
    {
        return $this->title.' (v'.$this->version.')';
    }
}
