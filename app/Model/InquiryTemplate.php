<?php

namespace App\Model;

use App\Enums\InquiryTemplateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Vorlage einer Elternabfrage. Inhalte liegen ausschließlich in Versionen
 * (InquiryTemplateVersion) – jede Änderung erzeugt eine neue Version.
 */
class InquiryTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'category', 'created_by', 'archived_at'];

    protected function casts(): array
    {
        return [
            'archived_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(InquiryTemplateVersion::class, 'template_id')->orderByDesc('version');
    }

    /** Aktuell freigegebene Version (für neue Abfragen). */
    public function approvedVersion(): HasOne
    {
        return $this->hasOne(InquiryTemplateVersion::class, 'template_id')
            ->where('status', InquiryTemplateStatus::Approved->value)
            ->latestOfMany('version');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(InquiryTemplateVersion::class, 'template_id')->latestOfMany('version');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** Offene (noch nicht freigegebene) Version, an der gerade gearbeitet wird. */
    public function openVersion(): ?InquiryTemplateVersion
    {
        return $this->versions()
            ->whereIn('status', [
                InquiryTemplateStatus::Draft->value,
                InquiryTemplateStatus::Rejected->value,
                InquiryTemplateStatus::InReview->value,
            ])
            ->first();
    }
}
