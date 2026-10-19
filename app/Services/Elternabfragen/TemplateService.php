<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryTemplateStatus;
use App\Model\InquiryEvent;
use App\Model\InquiryTemplate;
use App\Model\InquiryTemplateVersion;
use App\Model\User;
use App\Settings\ElternabfragenSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Prozess A: Vorlage erstellen, prüfen, freigeben, zurückgeben, archivieren.
 * Jede Änderung an einer freigegebenen Vorlage erzeugt eine neue Version.
 */
class TemplateService
{
    public const CONTENT_FIELDS = [
        'title', 'purpose', 'parent_info', 'responsible_office', 'target_hint', 'default_deadline_days',
        'retention_days', 'retention_note', 'is_sensitive', 'requires_all_guardians', 'fields',
    ];

    public function __construct(
        private readonly FieldSchema $schema,
        private readonly ElternabfragenSetting $settings,
    ) {}

    public function create(array $data, User $user): InquiryTemplateVersion
    {
        return DB::transaction(function () use ($data, $user) {
            $template = InquiryTemplate::create([
                'title' => $data['title'],
                'category' => $data['category'] ?? null,
                'created_by' => $user->id,
            ]);

            $version = $template->versions()->create($this->content($data) + [
                'version' => 1,
                'status' => InquiryTemplateStatus::Draft,
                'created_by' => $user->id,
            ]);

            InquiryEvent::log('template.created', ['template_version_id' => $version->id]);

            return $version;
        });
    }

    public function update(InquiryTemplateVersion $version, array $data): InquiryTemplateVersion
    {
        if (! $version->status->isEditable()) {
            abort(409, 'Nur Entwürfe können bearbeitet werden. Bitte eine neue Version anlegen.');
        }

        $version->update($this->content($data));
        $version->template->update(['title' => $data['title'], 'category' => $data['category'] ?? null]);

        return $version;
    }

    /**
     * Neue Version auf Basis der letzten Version (freigegeben oder ersetzt).
     */
    public function newVersion(InquiryTemplate $template, User $user): InquiryTemplateVersion
    {
        if ($template->isArchived()) {
            abort(409, 'Archivierte Vorlagen können nicht weiterentwickelt werden.');
        }
        if ($open = $template->openVersion()) {
            return $open;
        }

        $base = $template->versions()->first();

        $version = $template->versions()->create(collect($base->only(self::CONTENT_FIELDS))->all() + [
            'version' => (int) $template->versions()->max('version') + 1,
            'status' => InquiryTemplateStatus::Draft,
            'created_by' => $user->id,
        ]);

        InquiryEvent::log('template.version_created', ['template_version_id' => $version->id], null, ['based_on' => $base->version]);

        return $version;
    }

    public function submit(InquiryTemplateVersion $version): void
    {
        if (! $version->status->isEditable()) {
            abort(409, 'Diese Version wurde bereits eingereicht.');
        }

        $version->update(['status' => InquiryTemplateStatus::InReview, 'submitted_at' => now(), 'review_note' => null]);
        InquiryEvent::log('template.submitted', ['template_version_id' => $version->id]);
    }

    /**
     * Freigabe: Vier-Augen-Prinzip (Einstellung) und bei sensiblen Inhalten
     * die bestätigte datenschutzrechtliche Zusatzprüfung.
     */
    public function approve(InquiryTemplateVersion $version, User $user, bool $privacyChecked, ?string $note): void
    {
        if ($version->status !== InquiryTemplateStatus::InReview) {
            abort(409, 'Nur eingereichte Versionen können freigegeben werden.');
        }
        if ($this->settings->four_eyes && (int) $version->created_by === $user->id) {
            throw ValidationException::withMessages(['approve' => 'Vier-Augen-Prinzip: Die Freigabe muss durch eine andere Person erfolgen.']);
        }
        if ($version->is_sensitive && ! $privacyChecked) {
            throw ValidationException::withMessages(['privacy_checked' => 'Bei sensiblen Inhalten muss die datenschutzrechtliche Zusatzprüfung bestätigt werden.']);
        }

        DB::transaction(function () use ($version, $user, $privacyChecked, $note) {
            InquiryTemplateVersion::query()
                ->where('template_id', $version->template_id)
                ->where('status', InquiryTemplateStatus::Approved->value)
                ->get()
                ->each(fn (InquiryTemplateVersion $old) => $old->update(['status' => InquiryTemplateStatus::Superseded]));

            $version->update([
                'status' => InquiryTemplateStatus::Approved,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_note' => $note,
                'privacy_checked_by' => $version->is_sensitive && $privacyChecked ? $user->id : null,
                'privacy_checked_at' => $version->is_sensitive && $privacyChecked ? now() : null,
            ]);
            $version->template->update(['title' => $version->title]);

            InquiryEvent::log('template.approved', ['template_version_id' => $version->id], $note);
        });
    }

    public function reject(InquiryTemplateVersion $version, User $user, string $note): void
    {
        if ($version->status !== InquiryTemplateStatus::InReview) {
            abort(409, 'Nur eingereichte Versionen können zurückgegeben werden.');
        }

        $version->update([
            'status' => InquiryTemplateStatus::Rejected,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
        InquiryEvent::log('template.rejected', ['template_version_id' => $version->id], $note);
    }

    public function archive(InquiryTemplate $template): void
    {
        $template->update(['archived_at' => now()]);
        if ($latest = $template->latestVersion) {
            InquiryEvent::log('template.archived', ['template_version_id' => $latest->id]);
        }
    }

    private function content(array $data): array
    {
        return [
            'title' => $data['title'],
            'purpose' => $data['purpose'],
            'parent_info' => $data['parent_info'] ?? null,
            'responsible_office' => $data['responsible_office'] ?? null,
            'target_hint' => $data['target_hint'] ?? null,
            'default_deadline_days' => (int) ($data['default_deadline_days'] ?? 14),
            'retention_days' => isset($data['retention_days']) && $data['retention_days'] !== '' ? (int) $data['retention_days'] : null,
            'retention_note' => $data['retention_note'] ?? null,
            'is_sensitive' => (bool) ($data['is_sensitive'] ?? false),
            'requires_all_guardians' => (bool) ($data['requires_all_guardians'] ?? false),
            'fields' => $this->schema->normalizeDefinition($data['fields'] ?? []),
        ];
    }
}
