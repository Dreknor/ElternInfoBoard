<?php

namespace App\Model;

use App\Enums\InquiryAssignmentStatus;
use App\Services\Elternabfragen\GuardianConfirmation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Abfrage-Auftrag je Kind. Eine gültige Antwort eines Sorgeberechtigten genügt –
 * es sei denn, die Vorlage verlangt die Bestätigung aller Sorgeberechtigten.
 * Empfänger (InquiryRecipient) sind die zum Startzeitpunkt berechtigten Konten.
 */
class InquiryAssignment extends Model
{
    protected $fillable = [
        'inquiry_id', 'child_id', 'group_id', 'status', 'current_response_id', 'notified_at',
        'first_submitted_at', 'last_submitted_at', 'extended_until', 'correction_until',
        'has_problem', 'problem_note', 'note', 'reminder_count', 'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InquiryAssignmentStatus::class,
            'notified_at' => 'datetime',
            'first_submitted_at' => 'datetime',
            'last_submitted_at' => 'datetime',
            'extended_until' => 'datetime',
            'correction_until' => 'datetime',
            'has_problem' => 'boolean',
            'reminder_count' => 'integer',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class)->withTrashed();
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class)->withoutGlobalScopes();
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(InquiryRecipient::class, 'assignment_id');
    }

    public function drafts(): HasMany
    {
        return $this->hasMany(InquiryDraft::class, 'assignment_id');
    }

    public function responses(): HasMany
    {
        return $this->hasMany(InquiryResponse::class, 'assignment_id')->orderByDesc('version');
    }

    public function currentResponse(): BelongsTo
    {
        return $this->belongsTo(InquiryResponse::class, 'current_response_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InquiryDelivery::class, 'assignment_id')->orderByDesc('sent_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(InquiryEvent::class, 'assignment_id')->orderByDesc('id');
    }

    /** Ende der Abgabemöglichkeit: Frist der Abfrage oder individuelle Nachfrist. */
    public function effectiveDeadline(): CarbonInterface
    {
        $deadline = $this->inquiry->deadline_at;

        return $this->extended_until && $this->extended_until->greaterThan($deadline) ? $this->extended_until : $deadline;
    }

    public function isAnswered(): bool
    {
        return $this->current_response_id !== null;
    }

    /**
     * Antwort liegt vor und alle erforderlichen Bestätigungen sind erteilt.
     */
    public function isComplete(): bool
    {
        return $this->isAnswered() && $this->status !== InquiryAssignmentStatus::AwaitingConfirmation;
    }

    /**
     * Status aus den Daten ableiten (nach Abgabe, Bestätigung, Entwurf oder Klärung eines Problems).
     */
    public function recomputeStatus(): InquiryAssignmentStatus
    {
        if ($this->current_response_id !== null) {
            if (app(GuardianConfirmation::class)->isPending($this)) {
                return InquiryAssignmentStatus::AwaitingConfirmation;
            }

            $version = (int) InquiryResponse::whereKey($this->current_response_id)->value('version');

            return $version > 1 ? InquiryAssignmentStatus::Corrected : InquiryAssignmentStatus::Submitted;
        }
        if ($this->has_problem) {
            return InquiryAssignmentStatus::Problem;
        }

        return $this->drafts()->exists() ? InquiryAssignmentStatus::InProgress : InquiryAssignmentStatus::Open;
    }

    public function refreshStatus(): void
    {
        $this->status = $this->recomputeStatus();
        $this->save();
    }

    public function childName(): string
    {
        return trim(($this->child?->first_name ?? '').' '.($this->child?->last_name ?? '')) ?: 'Kind #'.$this->child_id;
    }
}
