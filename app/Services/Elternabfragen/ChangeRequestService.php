<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryChangeRequestStatus;
use App\Enums\InquiryReviewStatus;
use App\Enums\InquiryStatus;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryEvent;
use App\Model\InquiryResponse;
use App\Model\User;
use App\Settings\ElternabfragenSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Änderungsmeldungen (§ 6.4): Eltern melden geänderte Angaben zu einer bereits abgegebenen
 * Antwort – auch nach Ablauf des Korrekturfensters oder nach Abschluss der Abfrage
 * (z. B. Umzug während des Schuljahres). Die Meldung wird nicht direkt wirksam: Das
 * Sekretariat prüft sie und übernimmt sie als neue Antwortversion oder lehnt sie ab.
 */
class ChangeRequestService
{
    public function __construct(
        private readonly InquiryAccess $access,
        private readonly ResponseService $responses,
        private readonly FieldSchema $schema,
        private readonly DeliveryService $delivery,
        private readonly ElternabfragenSetting $settings,
    ) {}

    /**
     * Darf der Nutzer für diesen Auftrag eine Änderung melden?
     *
     * `visible`: der Bereich „Änderung melden“ ist für den Nutzer überhaupt relevant
     * (Sorgerecht, Antwort liegt vor, Abfrage erlaubt Meldungen).
     *
     * @return array{allowed: bool, visible: bool, reason: ?string, pending: ?InquiryChangeRequest, successor: ?InquiryAssignment}
     */
    public function state(User $user, InquiryAssignment $assignment): array
    {
        $inquiry = $assignment->inquiry;
        $result = ['allowed' => false, 'visible' => false, 'reason' => null, 'pending' => null, 'successor' => null];

        if (! $inquiry || ! $inquiry->allow_change_requests || $inquiry->purged_at !== null
            || ! in_array($inquiry->status, [InquiryStatus::Running, InquiryStatus::Closed], true)
            || ! $assignment->isAnswered() || ! $this->access->canAnswer($user, $assignment)) {
            return $result;
        }

        // Solange Eltern selbst korrigieren dürfen, ist keine Meldung nötig
        if ($this->access->state($user, $assignment)['can_correct']) {
            return $result;
        }

        $result['visible'] = true;
        $result['pending'] = InquiryChangeRequest::pending()->where('assignment_id', $assignment->id)->with('user:id,name')->latest('id')->first();

        // Neuere Abfrage derselben Reihe (z. B. neues Schuljahr): Änderungen dort angeben
        $successor = $this->successor($assignment);
        if ($successor !== null) {
            $result['successor'] = $successor;
            $result['reason'] = 'Diese Angaben werden inzwischen mit „'.$successor->inquiry->title.'“ abgefragt. Bitte melden Sie Änderungen dort.';

            return $result;
        }

        if ($result['pending'] && $result['pending']->user_id !== $user->id) {
            $result['reason'] = 'Für dieses Kind liegt bereits eine Änderungsmeldung von '.($result['pending']->user?->name ?? 'einer anderen sorgeberechtigten Person').' vor, die noch bearbeitet wird.';

            return $result;
        }

        $result['allowed'] = true;

        return $result;
    }

    /**
     * Änderung melden. Eine noch offene Meldung desselben Nutzers wird dabei ersetzt.
     *
     * @param  array<string, mixed>  $input  vollständige neue Angaben (ohne Dateien)
     * @param  array<string, UploadedFile>  $files
     * @param  array{channel?: string, ip?: ?string, user_agent?: ?string}  $meta
     *
     * @throws ValidationException
     */
    public function submit(InquiryAssignment $assignment, User $user, array $input, array $files, string $message, array $meta = []): InquiryChangeRequest
    {
        $state = $this->state($user, $assignment);
        if (! $state['allowed']) {
            throw new HttpException($state['visible'] ? 409 : 403, $state['reason'] ?? 'Eine Änderungsmeldung ist hier nicht möglich.');
        }

        $message = trim($message);
        if ($message === '') {
            throw ValidationException::withMessages(['message' => 'Bitte beschreiben Sie kurz, was sich geändert hat.']);
        }

        $fields = $this->responses->fields($assignment);
        $current = $assignment->currentResponse()->first();
        $existing = $current?->answers ?? [];
        $children = $this->responses->childOptions($fields, $user);

        Validator::make(
            ['answers' => $files + $input],
            $this->schema->rules($fields, true, $this->settings->upload_max_kb, $existing, $children),
            $this->schema->messages($fields),
        )->validate();

        $answers = $this->schema->sanitize($fields, $input, $children);
        if ($files === [] && ! $this->differs($fields, $existing, $answers)) {
            throw ValidationException::withMessages(['answers' => 'Sie haben keine Angaben geändert.']);
        }

        $request = DB::transaction(function () use ($assignment, $user, $fields, $files, $existing, $answers, $message, $meta, $current) {
            $answers += $this->responses->storeFiles($assignment, $fields, $files, $existing);

            InquiryChangeRequest::pending()->where('assignment_id', $assignment->id)->where('user_id', $user->id)->get()
                ->each(fn (InquiryChangeRequest $old) => $this->close($old, InquiryChangeRequestStatus::Withdrawn, null, 'Durch eine neue Meldung ersetzt.'));

            $request = InquiryChangeRequest::create([
                'inquiry_id' => $assignment->inquiry_id,
                'assignment_id' => $assignment->id,
                'child_id' => $assignment->child_id,
                'user_id' => $user->id,
                'base_response_id' => $current?->id,
                'answers' => $answers,
                'message' => Str::limit($message, 2000, ''),
                'status' => InquiryChangeRequestStatus::Pending,
                'channel' => in_array($meta['channel'] ?? null, ['web', 'app'], true) ? $meta['channel'] : 'web',
                'ip_anonymized' => ResponseService::anonymizeIp($meta['ip'] ?? null),
                'user_agent' => isset($meta['user_agent']) ? Str::limit((string) $meta['user_agent'], 250, '') : null,
            ]);

            InquiryEvent::create([
                'inquiry_id' => $assignment->inquiry_id,
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
                'action' => 'change.requested',
                'note' => $request->message,
                'data' => ['change_request_id' => $request->id, 'base_version' => $current?->version],
            ]);

            return $request;
        });

        $this->delivery->changeRequested($request->setRelation('assignment', $assignment)->setRelation('user', $user));

        return $request;
    }

    /** Eigene offene Meldung zurückziehen. */
    public function withdraw(InquiryAssignment $assignment, User $user): void
    {
        $request = InquiryChangeRequest::pending()->where('assignment_id', $assignment->id)->where('user_id', $user->id)->latest('id')->first();
        abort_unless($request, 404);

        $this->close($request, InquiryChangeRequestStatus::Withdrawn, null, null);
        InquiryEvent::create([
            'inquiry_id' => $assignment->inquiry_id,
            'assignment_id' => $assignment->id,
            'user_id' => $user->id,
            'action' => 'change.withdrawn',
            'data' => ['change_request_id' => $request->id],
        ]);
    }

    /**
     * Meldung übernehmen: Die gemeldeten Angaben werden als neue, gültige Antwortversion der
     * meldenden Person gespeichert und gelten damit als geprüft. Bei gemeinsamen Erklärungen
     * müssen die weiteren Sorgeberechtigten die neue Version bestätigen.
     */
    public function accept(InquiryChangeRequest $request, User $handler, ?string $note = null): InquiryResponse
    {
        $this->assertHandleable($request);

        $response = DB::transaction(function () use ($request, $handler, $note) {
            /** @var InquiryAssignment $locked */
            $locked = InquiryAssignment::query()->whereKey($request->assignment_id)->lockForUpdate()->firstOrFail();
            $locked->setRelation('inquiry', $request->inquiry);
            $locked->setRelation('child', $request->assignment->child);

            /** @var InquiryChangeRequest $fresh */
            $fresh = InquiryChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if (! $fresh->isPending()) {
                throw new HttpException(409, 'Die Änderungsmeldung wurde bereits bearbeitet.');
            }
            if (! $locked->isAnswered()) {
                throw new HttpException(409, 'Für dieses Kind liegt keine gültige Antwort mehr vor (z. B. nach einem Neustart). Die Meldung kann nur abgelehnt werden.');
            }

            $note = filled($note) ? Str::limit(trim($note), 2000, '') : null;
            $response = $this->responses->createVersion(
                $locked,
                $request->user ?? $handler,
                $request->answers,
                // Nachweisdaten der Meldung (IP bereits gekürzt)
                ['channel' => $request->channel, 'ip' => $request->ip_anonymized, 'user_agent' => $request->user_agent],
                'Änderungsmeldung: '.$request->message,
                [
                    'review_status' => InquiryReviewStatus::Approved,
                    'reviewed_by' => $handler->id,
                    'reviewed_at' => now(),
                    'review_note' => $note,
                ],
                'response.changed',
            );

            $fresh->forceFill([
                'status' => InquiryChangeRequestStatus::Accepted,
                'handled_by' => $handler->id,
                'handled_at' => now(),
                'handled_note' => $note,
                'response_id' => $response->id,
            ])->save();

            InquiryEvent::create([
                'inquiry_id' => $request->inquiry_id,
                'assignment_id' => $request->assignment_id,
                'user_id' => $handler->id,
                'action' => 'change.accepted',
                'note' => $note,
                'data' => ['change_request_id' => $request->id, 'version' => $response->version],
            ]);

            return $response;
        });

        $request->refresh()->load(['assignment.inquiry.templateVersion', 'assignment.child', 'user']);
        $this->delivery->changeHandled($request);

        return $response;
    }

    public function reject(InquiryChangeRequest $request, User $handler, string $note): void
    {
        $this->assertHandleable($request);
        if (blank($note)) {
            throw ValidationException::withMessages(['handled_note' => 'Bitte begründen Sie die Ablehnung – die Begründung erhalten die Eltern.']);
        }

        DB::transaction(function () use ($request, $handler, $note) {
            $fresh = InquiryChangeRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();
            if (! $fresh->isPending()) {
                throw new HttpException(409, 'Die Änderungsmeldung wurde bereits bearbeitet.');
            }
            $this->close($fresh, InquiryChangeRequestStatus::Rejected, $handler, Str::limit(trim($note), 2000, ''));

            InquiryEvent::create([
                'inquiry_id' => $request->inquiry_id,
                'assignment_id' => $request->assignment_id,
                'user_id' => $handler->id,
                'action' => 'change.rejected',
                'note' => $fresh->handled_note,
                'data' => ['change_request_id' => $request->id],
            ]);
        });

        $request->refresh()->load(['assignment.inquiry.templateVersion', 'assignment.child', 'user']);
        $this->delivery->changeHandled($request);
    }

    /**
     * Geänderte Felder gegenüber der Antwortversion, auf die sich die Meldung bezieht
     * (bzw. gegenüber der heute gültigen Version).
     *
     * @return list<array{field: array, old: mixed, new: mixed, changed: bool}>
     */
    public function comparison(InquiryChangeRequest $request, ?InquiryResponse $against = null): array
    {
        $against ??= $request->baseResponse;
        $old = $against?->answers ?? [];
        $rows = [];

        foreach ($request->inquiry->templateVersion->fields ?? [] as $field) {
            if (($field['type'] ?? null) === 'info' || ! isset($field['key'])) {
                continue;
            }
            $key = $field['key'];
            $rows[] = [
                'field' => $field,
                'old' => $old[$key] ?? null,
                'new' => $request->answers[$key] ?? null,
                'changed' => $this->normalize($old[$key] ?? null) !== $this->normalize($request->answers[$key] ?? null),
            ];
        }

        return $rows;
    }

    /** Offene Meldungen für die Verwaltung (nur mit Einsicht in Antwortinhalte). */
    public static function pendingCount(): int
    {
        if (! auth()->user()?->can('view elternabfragen answers')) {
            return 0;
        }

        return InquiryChangeRequest::pending()->whereHas('inquiry', fn ($q) => $q->whereNull('purged_at'))->count();
    }

    /** Neuere, freigegebene Abfrage derselben Reihe, in der das Kind einen Auftrag hat. */
    public function successor(InquiryAssignment $assignment): ?InquiryAssignment
    {
        $ids = [];
        $current = [$assignment->inquiry_id];
        // Kette der Folgeabfragen (Vorjahr → aktuelles Jahr → …), höchstens wenige Stufen
        for ($depth = 0; $depth < 10 && $current !== []; $depth++) {
            $current = Inquiry::query()->whereIn('previous_inquiry_id', $current)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = array_merge($ids, $current);
        }
        if ($ids === []) {
            return null;
        }

        return InquiryAssignment::query()
            ->with('inquiry')
            ->where('child_id', $assignment->child_id)
            ->whereIn('inquiry_id', $ids)
            ->whereHas('inquiry', fn ($q) => $q->whereIn('status', [InquiryStatus::Running->value, InquiryStatus::Closed->value])->whereNull('purged_at'))
            ->orderByDesc('inquiry_id')
            ->first();
    }

    private function assertHandleable(InquiryChangeRequest $request): void
    {
        $request->loadMissing(['inquiry.templateVersion', 'assignment.child', 'user']);
        if ($request->inquiry === null || $request->inquiry->purged_at !== null || $request->assignment === null) {
            abort(409, 'Die Antworten dieser Abfrage wurden bereits gelöscht.');
        }
        if (! $request->isPending()) {
            abort(409, 'Die Änderungsmeldung wurde bereits bearbeitet.');
        }
    }

    private function close(InquiryChangeRequest $request, InquiryChangeRequestStatus $status, ?User $handler, ?string $note): void
    {
        $request->forceFill([
            'status' => $status,
            'handled_by' => $handler?->id,
            'handled_at' => now(),
            'handled_note' => $note,
        ])->save();

        $this->discardFiles($request);
    }

    /** Neu hochgeladene Dateien einer nicht übernommenen Meldung entfernen. */
    private function discardFiles(InquiryChangeRequest $request): void
    {
        $used = InquiryResponse::where('assignment_id', $request->assignment_id)->pluck('answers')
            ->flatMap(fn ($answers) => collect(is_array($answers) ? $answers : [])->filter(fn ($v) => is_array($v) && isset($v['path']))->pluck('path'))
            ->all();

        foreach ($request->answers ?? [] as $value) {
            if (is_array($value) && isset($value['path']) && ! in_array($value['path'], $used, true)) {
                Storage::disk(ResponseService::DISK)->delete($value['path']);
            }
        }
    }

    /** Weicht mindestens eine Angabe (ohne Dateien) von der bisherigen Antwort ab? */
    private function differs(array $fields, array $existing, array $answers): bool
    {
        foreach ($fields as $field) {
            if (! isset($field['key']) || in_array($field['type'] ?? null, ['info', 'file'], true)) {
                continue;
            }
            if ($this->normalize($existing[$field['key']] ?? null) !== $this->normalize($answers[$field['key']] ?? null)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(mixed $value): string
    {
        if ($value === '' || $value === []) {
            $value = null;
        }
        if (is_array($value)) {
            if (isset($value['path'])) {
                return 'file:'.$value['path'];
            }
            if (array_is_list($value)) {
                sort($value);
            } else {
                ksort($value);
            }
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
