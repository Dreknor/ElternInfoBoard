<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryStatus;
use App\Enums\InquiryTemplateStatus;
use App\Jobs\DeliverInquiryNotifications;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryEvent;
use App\Model\InquiryRecipient;
use App\Model\InquiryTemplateVersion;
use App\Model\User;
use App\Settings\ElternabfragenSetting;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prozess B: Abfrage vorbereiten, freigeben und starten; nach dem Start nur
 * dokumentierte Änderungen (Frist, Abgleich der Zielgruppe, Abschluss).
 */
class InquiryService
{
    /** Laufende Abfragen werden … Tage nach der letzten Frist automatisch abgeschlossen. */
    public const AUTO_CLOSE_AFTER_DAYS = 30;

    public function __construct(
        private readonly TargetResolver $targets,
        private readonly ElternabfragenSetting $settings,
        private readonly PreviousAnswers $previousAnswers,
    ) {}

    public function create(array $data, User $user): Inquiry
    {
        $version = InquiryTemplateVersion::findOrFail($data['template_version_id']);
        if ($version->status !== InquiryTemplateStatus::Approved) {
            throw ValidationException::withMessages(['template_version_id' => 'Abfragen können nur aus freigegebenen Vorlagen erstellt werden.']);
        }

        $this->assertPreviousInquiry($data['previous_inquiry_id'] ?? null, $version);

        $inquiry = Inquiry::create($this->attributes($data) + [
            'template_version_id' => $version->id,
            'status' => InquiryStatus::Draft,
            'created_by' => $user->id,
            // null = unbefristet: keine automatische Löschung, nur manuell durch die Verwaltung
            'retention_days' => $version->retention_unlimited ? null : ($version->retention_days ?? $this->settings->default_retention_days),
        ]);

        InquiryEvent::log('inquiry.created', ['inquiry_id' => $inquiry->id], null, ['template_version' => $version->label()]);

        return $inquiry;
    }

    public function update(Inquiry $inquiry, array $data): Inquiry
    {
        $inquiry->assertStatus(InquiryStatus::Draft);
        $this->assertPreviousInquiry($data['previous_inquiry_id'] ?? null, $inquiry->templateVersion, $inquiry->id);
        $inquiry->update($this->attributes($data));

        return $inquiry;
    }

    public function submit(Inquiry $inquiry): void
    {
        $inquiry->assertStatus(InquiryStatus::Draft);
        $this->assertPlausible($inquiry);

        $inquiry->update(['status' => InquiryStatus::PendingApproval, 'submitted_at' => now(), 'review_note' => null]);
        InquiryEvent::log('inquiry.submitted', ['inquiry_id' => $inquiry->id]);
    }

    /**
     * Verbindliche Freigabe nach Prüfung von Zielgruppe, Kindern, Frist, Absender,
     * Pflichtfeldern und Kanälen. Erwartete Anzahl wird festgehalten.
     */
    public function approve(Inquiry $inquiry, User $user, ?string $note = null): void
    {
        $inquiry->assertStatus(InquiryStatus::PendingApproval);
        if ($this->settings->four_eyes && (int) $inquiry->created_by === $user->id) {
            throw ValidationException::withMessages(['approve' => 'Vier-Augen-Prinzip: Die Freigabe muss durch eine andere Person erfolgen.']);
        }
        $this->assertPlausible($inquiry);

        $preview = $this->targets->preview($inquiry);

        $inquiry->update([
            'status' => InquiryStatus::Scheduled,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'review_note' => $note,
            'expected_children' => $preview['children'],
            'expected_accounts' => $preview['accounts'],
        ]);
        InquiryEvent::log('inquiry.approved', ['inquiry_id' => $inquiry->id], $note, [
            'expected_children' => $preview['children'],
            'expected_accounts' => $preview['accounts'],
        ]);

        if ($inquiry->starts_at->lessThanOrEqualTo(now())) {
            $this->start($inquiry);
        }
    }

    public function reject(Inquiry $inquiry, string $note): void
    {
        $inquiry->assertStatus(InquiryStatus::PendingApproval);
        $inquiry->update(['status' => InquiryStatus::Draft, 'review_note' => $note]);
        InquiryEvent::log('inquiry.rejected', ['inquiry_id' => $inquiry->id], $note);
    }

    /**
     * Start: erst jetzt werden die individuellen Aufträge für die berechtigten
     * Elternkonten erzeugt und die Benachrichtigungen versendet.
     */
    public function start(Inquiry $inquiry): int
    {
        $inquiry->assertStatus(InquiryStatus::Scheduled);

        $created = DB::transaction(function () use ($inquiry) {
            $inquiry->update(['status' => InquiryStatus::Running, 'released_at' => now()]);

            return $this->createAssignments($inquiry);
        });

        InquiryEvent::log('inquiry.started', ['inquiry_id' => $inquiry->id], null, ['assignments' => count($created)]);
        $this->dispatchRelease($inquiry, $created);

        return count($created);
    }

    /**
     * Nachträglich hinzugekommene Kinder der Zielgruppe ergänzen (nie entfernen).
     */
    public function syncTargets(Inquiry $inquiry): int
    {
        $inquiry->assertStatus(InquiryStatus::Running);

        $created = DB::transaction(fn () => $this->createAssignments($inquiry));
        InquiryEvent::log('inquiry.synced', ['inquiry_id' => $inquiry->id], null, ['added' => count($created)]);
        $this->dispatchRelease($inquiry, $created);

        return count($created);
    }

    public function changeDeadline(Inquiry $inquiry, CarbonInterface $deadline, string $reason): void
    {
        $inquiry->assertStatus(InquiryStatus::Scheduled, InquiryStatus::Running);
        if ($deadline->lessThanOrEqualTo($inquiry->starts_at)) {
            throw ValidationException::withMessages(['deadline_at' => 'Die Frist muss nach dem Startzeitpunkt liegen.']);
        }

        $old = $inquiry->deadline_at;
        $inquiry->update(['deadline_at' => $deadline]);
        InquiryEvent::log('inquiry.deadline_changed', ['inquiry_id' => $inquiry->id], $reason, [
            'from' => $old->toIso8601String(),
            'to' => $deadline->toIso8601String(),
        ]);
    }

    /**
     * Änderbare Einstellungen nach der Freigabe (Kanäle, Erinnerungen, Zuständigkeit, Mitarbeitersicht).
     */
    public function updateSettings(Inquiry $inquiry, array $data): void
    {
        $inquiry->fill(collect($data)->only([
            'responsible_user_id', 'notify_mail', 'notify_push', 'auto_reminders', 'staff_visible', 'correction_window_days',
        ])->all());

        if ($inquiry->isDirty()) {
            $changes = $inquiry->getDirty();
            $inquiry->save();
            InquiryEvent::log('inquiry.settings_changed', ['inquiry_id' => $inquiry->id], null, $changes);
        }
    }

    /**
     * Vorabfrage nachträglich setzen oder entfernen (auch bei laufenden oder abgeschlossenen
     * Abfragen). Bereits abgegebene Antworten werden neu verglichen; die Vorbefüllung gilt
     * für alle, die noch nicht geantwortet haben.
     */
    public function setPreviousInquiry(Inquiry $inquiry, ?int $previousId): int
    {
        $inquiry->assertStatus(InquiryStatus::Draft, InquiryStatus::PendingApproval, InquiryStatus::Scheduled, InquiryStatus::Running, InquiryStatus::Closed);
        if ($inquiry->purged_at) {
            throw ValidationException::withMessages(['previous_inquiry_id' => 'Die Antworten dieser Abfrage wurden bereits gelöscht.']);
        }
        $this->assertPreviousInquiry($previousId, $inquiry->templateVersion, $inquiry->id);

        $before = $inquiry->previous_inquiry_id;
        $inquiry->update(['previous_inquiry_id' => $previousId ?: null]);
        $compared = $this->previousAnswers->recompute($inquiry);

        if ($before !== $inquiry->previous_inquiry_id) {
            InquiryEvent::log('inquiry.previous_set', ['inquiry_id' => $inquiry->id], null, [
                'previous_inquiry_id' => $inquiry->previous_inquiry_id,
                'compared_responses' => $compared,
            ]);
        }

        return $compared;
    }

    /**
     * Abfrage beenden – auch vorzeitig vor Fristende oder als geplante Abfrage vor dem Start.
     * Danach sind keine Abgaben, Korrekturen, Bestätigungen und Erinnerungen mehr möglich.
     */
    public function close(Inquiry $inquiry, ?string $note = null): void
    {
        $inquiry->assertStatus(InquiryStatus::Scheduled, InquiryStatus::Running);

        $now = now();
        $wasScheduled = $inquiry->status === InquiryStatus::Scheduled;
        $inquiry->update(['status' => InquiryStatus::Closed, 'closed_at' => $now]);
        InquiryEvent::log('inquiry.closed', ['inquiry_id' => $inquiry->id], $note, array_filter([
            'early' => $now->lessThan($inquiry->deadline_at),
            'before_start' => $wasScheduled,
        ]));
    }

    /**
     * Inhaltliche Änderung nach dem Start: neue Abfrage als Entwurf (neue Version).
     */
    public function duplicate(Inquiry $inquiry, User $user): Inquiry
    {
        $version = $inquiry->templateVersion;
        $approved = $version->template?->approvedVersion ?? $version;

        $copy = Inquiry::create(collect($inquiry->only([
            'title', 'intro', 'target_type', 'target_group_ids', 'responsible_user_id', 'notify_mail', 'notify_push',
            'auto_reminders', 'staff_visible', 'correction_window_days', 'retention_days',
        ]))->all() + [
            'template_version_id' => $approved->status === InquiryTemplateStatus::Approved ? $approved->id : $version->id,
            // Angaben dieser Abfrage vorbefüllen und Änderungen ausweisen
            'previous_inquiry_id' => $inquiry->purged_at ? null : $inquiry->id,
            'status' => InquiryStatus::Draft,
            'created_by' => $user->id,
            'starts_at' => now()->addDay()->startOfHour(),
            'deadline_at' => now()->addDays(1 + ($version->default_deadline_days ?: 14))->endOfDay(),
        ]);

        InquiryEvent::log('inquiry.created', ['inquiry_id' => $copy->id], 'Kopie von Abfrage #'.$inquiry->id);

        return $copy;
    }

    /**
     * Geplante Abfragen starten und abgelaufene automatisch abschließen.
     *
     * @return array{started: int, closed: int}
     */
    public function runSchedule(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $started = 0;
        $closed = 0;

        Inquiry::query()->where('status', InquiryStatus::Scheduled->value)->where('starts_at', '<=', $now)->get()
            ->each(function (Inquiry $inquiry) use (&$started) {
                $this->start($inquiry);
                $started++;
            });

        Inquiry::query()->where('status', InquiryStatus::Running->value)
            ->where('deadline_at', '<=', $now->copy()->subDays(self::AUTO_CLOSE_AFTER_DAYS))
            ->get()
            ->each(function (Inquiry $inquiry) use ($now, &$closed) {
                $pendingExtension = $inquiry->assignments()
                    ->where('extended_until', '>', $now->copy()->subDays(self::AUTO_CLOSE_AFTER_DAYS))
                    ->exists();
                if (! $pendingExtension) {
                    $this->close($inquiry, 'Automatisch abgeschlossen ('.self::AUTO_CLOSE_AFTER_DAYS.' Tage nach Fristende).');
                    $closed++;
                }
            });

        return ['started' => $started, 'closed' => $closed];
    }

    /**
     * @return list<int> IDs der neu erzeugten Aufträge
     */
    private function createAssignments(Inquiry $inquiry): array
    {
        $existing = $inquiry->assignments()->pluck('child_id')->flip();
        $created = [];

        foreach ($this->targets->children($inquiry) as $child) {
            if ($existing->has($child->id)) {
                continue;
            }

            $assignment = InquiryAssignment::create([
                'inquiry_id' => $inquiry->id,
                'child_id' => $child->id,
                'group_id' => $child->class_id ?? $child->group_id,
                'status' => InquiryAssignmentStatus::Open,
            ]);

            foreach ($this->targets->recipients($child) as $user) {
                InquiryRecipient::create(['assignment_id' => $assignment->id, 'user_id' => $user->id]);
            }

            $created[] = $assignment->id;
        }

        return $created;
    }

    private function dispatchRelease(Inquiry $inquiry, array $assignmentIds): void
    {
        foreach (array_chunk($assignmentIds, 200) as $chunk) {
            DeliverInquiryNotifications::dispatch($inquiry->id, $chunk);
        }
    }

    private function assertPlausible(Inquiry $inquiry): void
    {
        $errors = [];
        if ($inquiry->deadline_at->lessThanOrEqualTo($inquiry->starts_at)) {
            $errors['deadline_at'] = 'Die Rückgabefrist muss nach dem Startzeitpunkt liegen.';
        }
        if ($inquiry->deadline_at->isPast()) {
            $errors['deadline_at'] = 'Die Rückgabefrist liegt in der Vergangenheit.';
        }
        if ($inquiry->target_type !== Inquiry::TARGET_ALL && empty($inquiry->target_group_ids)) {
            $errors['target_group_ids'] = 'Bitte eine Zielgruppe wählen.';
        }
        if ($inquiry->templateVersion?->status !== InquiryTemplateStatus::Approved) {
            $errors['template_version_id'] = 'Die verwendete Vorlagenversion ist nicht (mehr) freigegeben.';
        }
        if (! $inquiry->notify_mail && ! $inquiry->notify_push) {
            $errors['notify_mail'] = 'Mindestens ein Benachrichtigungskanal muss aktiv sein.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function attributes(array $data): array
    {
        $targetType = ($data['target_type'] ?? Inquiry::TARGET_GROUPS) === Inquiry::TARGET_ALL ? Inquiry::TARGET_ALL : Inquiry::TARGET_GROUPS;

        return [
            'title' => $data['title'],
            'intro' => $data['intro'] ?? null,
            'previous_inquiry_id' => $data['previous_inquiry_id'] ?? null,
            'starts_at' => $data['starts_at'],
            'deadline_at' => $data['deadline_at'],
            'target_type' => $targetType,
            'target_group_ids' => $targetType === Inquiry::TARGET_ALL ? [] : array_values(array_map('intval', $data['target_group_ids'] ?? [])),
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'notify_mail' => (bool) ($data['notify_mail'] ?? false),
            'notify_push' => (bool) ($data['notify_push'] ?? false),
            'auto_reminders' => (bool) ($data['auto_reminders'] ?? false),
            'staff_visible' => (bool) ($data['staff_visible'] ?? false),
            'correction_window_days' => (int) ($data['correction_window_days'] ?? $this->settings->correction_window_days),
        ];
    }

    /**
     * Vorabfragen für die Vorbefüllung: gestartete oder abgeschlossene Abfragen derselben
     * Vorlage, deren Antworten noch nicht gelöscht sind.
     */
    public function previousCandidates(?Inquiry $except = null): Collection
    {
        return Inquiry::query()
            ->with('templateVersion:id,template_id')
            ->whereIn('status', [InquiryStatus::Running->value, InquiryStatus::Closed->value])
            ->whereNull('purged_at')
            ->when($except?->exists, fn ($q) => $q->whereKeyNot($except->id))
            ->orderByDesc('deadline_at')
            ->get();
    }

    private function assertPreviousInquiry(mixed $previousId, ?InquiryTemplateVersion $version, ?int $selfId = null): void
    {
        if (! $previousId) {
            return;
        }

        $previous = Inquiry::with('templateVersion:id,template_id')->find($previousId);
        $message = match (true) {
            ! $previous, (int) $previousId === $selfId => 'Die gewählte Vorabfrage gibt es nicht.',
            $previous->purged_at !== null => 'Die Antworten der gewählten Vorabfrage wurden bereits gelöscht.',
            ! in_array($previous->status, [InquiryStatus::Running, InquiryStatus::Closed], true) => 'Als Vorabfrage eignen sich nur gestartete oder abgeschlossene Abfragen.',
            $previous->templateVersion?->template_id !== $version?->template_id => 'Vorbefüllen ist nur aus einer Abfrage derselben Vorlage möglich.',
            default => null,
        };

        if ($message) {
            throw ValidationException::withMessages(['previous_inquiry_id' => $message]);
        }
    }
}
