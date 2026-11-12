<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Enums\InquiryChangeRequestStatus;
use App\Enums\InquiryReceiptKind;
use App\Mail\ElternabfrageMail;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryDelivery;
use App\Model\InquiryReceipt;
use App\Model\InquiryRecipient;
use App\Model\InquiryResponse;
use App\Model\InquiryTemplateVersion;
use App\Model\Notification;
use App\Model\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Benachrichtigungen (E-Mail, Glocke inkl. App-/Web-Push nach Nutzerwahl) mit
 * Protokoll je Empfänger und Kanal. Nicht zustellbare Aufträge werden als
 * „Technisches Problem“ gekennzeichnet.
 */
class DeliveryService
{
    public const NOTIFICATION_TYPE = 'Elternabfragen';

    public const ICON = 'fas fa-clipboard-check';

    /**
     * Erstbenachrichtigung nach dem Start.
     */
    public function notifyRelease(InquiryAssignment $assignment): bool
    {
        return $this->deliver($assignment, InquiryDelivery::KIND_RELEASE, 'auto', null);
    }

    /**
     * Erinnerung zustellen. Zähler und Zeitpunkt setzt der ReminderService bereits beim
     * Einplanen, damit eine verzögerte Queue keine doppelten Erinnerungen erzeugt.
     */
    public function remind(InquiryAssignment $assignment, string $kind, string $trigger, ?int $actorId): bool
    {
        if ($assignment->isAnswered()) {
            // Beantwortet, aber noch nicht von allen bestätigt: nur die Fehlenden erinnern
            app(GuardianConfirmation::class)->pendingUsers($assignment)
                ->each(fn (User $user) => $this->requestConfirmation($assignment, $user, $trigger, $actorId));

            return true;
        }

        return $this->deliver($assignment, $kind, $trigger, $actorId);
    }

    /**
     * Eingangsbestätigung an die absendende Person; weitere Sorgeberechtigte werden
     * informiert, dass die Abfrage beantwortet wurde (getrennte Haushalte).
     * Die E-Mail verschickt sendReceipt() zusammen mit der Erklärungsquittung (eine Mail);
     * nur Antworten ohne Quittung (Altbestand) erhalten hier die bisherige Eingangsbestätigung.
     */
    public function confirm(InquiryResponse $response): void
    {
        $assignment = $response->assignment;
        $inquiry = $assignment->inquiry;
        $submitter = $response->user;
        $url = url('elternabfragen/'.$assignment->id);
        $child = $assignment->child?->first_name ?? $assignment->childName();

        if ($submitter) {
            Notification::create([
                'type' => self::NOTIFICATION_TYPE,
                'user_id' => $submitter->id,
                'title' => 'Abfrage eingereicht',
                'message' => '„'.$inquiry->title.'“ für '.$child.' ist eingegangen.',
                'url' => $url,
                'icon' => self::ICON,
            ]);
            $this->log($assignment, $submitter->id, InquiryDelivery::KIND_CONFIRMATION, InquiryDelivery::CHANNEL_BELL, 'auto', null, true);

            if (filled($submitter->email) && ! $this->hasReceipt($response->id)) {
                $this->sendMail($assignment, $submitter, InquiryDelivery::KIND_CONFIRMATION, 'auto', null, new ElternabfrageMail(
                    kind: ElternabfrageMail::KIND_CONFIRMATION,
                    userName: $submitter->name,
                    inquiryTitle: $inquiry->title,
                    childName: $assignment->childName(),
                    deadline: $assignment->effectiveDeadline()->format('d.m.Y H:i'),
                    url: $url,
                    submittedAt: $response->submitted_at->format('d.m.Y H:i'),
                    version: $response->version,
                ));
            }
        }

        $confirmation = app(GuardianConfirmation::class);
        if ($confirmation->required($assignment)) {
            // Weitere Sorgeberechtigte müssen genau diese Version bestätigen
            $confirmation->pendingUsers($assignment)
                ->each(fn (User $user) => $this->requestConfirmation($assignment, $user, 'auto', null, $submitter));

            return;
        }

        $assignment->recipients()->with('user')->get()
            ->filter(fn (InquiryRecipient $r) => $r->user && $r->user_id !== $response->user_id)
            ->each(fn (InquiryRecipient $r) => Notification::create([
                'type' => self::NOTIFICATION_TYPE,
                'user_id' => $r->user_id,
                'title' => 'Abfrage beantwortet',
                'message' => '„'.$inquiry->title.'“ für '.$child.' wurde von '.($submitter?->name ?? 'einer sorgeberechtigten Person').' '
                    .($response->version > 1 ? 'korrigiert.' : 'beantwortet.'),
                'url' => $url,
                'icon' => self::ICON,
            ]));
    }

    /**
     * Bitte an eine weitere sorgeberechtigte Person, die eingereichte Antwort zu bestätigen
     * (nach dem Einreichen und als Erinnerung).
     */
    public function requestConfirmation(InquiryAssignment $assignment, User $user, string $trigger, ?int $actorId, ?User $submitter = null): bool
    {
        $inquiry = $assignment->inquiry;
        $url = url('elternabfragen/'.$assignment->id);
        $kind = InquiryDelivery::KIND_CONFIRMATION_REQUEST;

        Notification::create([
            'type' => self::NOTIFICATION_TYPE,
            'user_id' => $user->id,
            'title' => 'Bitte bestätigen: Elternabfrage',
            'message' => '„'.$inquiry->title.'“ für '.($assignment->child?->first_name ?? 'Ihr Kind').' wurde'
                .($submitter ? ' von '.$submitter->name : '').' beantwortet. Bitte prüfen und bestätigen Sie die Angaben.',
            'url' => $url,
            'icon' => self::ICON,
            'important' => true,
        ]);
        $this->log($assignment, $user->id, $kind, InquiryDelivery::CHANNEL_BELL, $trigger, $actorId, true);

        if (! $inquiry->notify_mail || blank($user->email)) {
            return true;
        }

        return $this->sendMail($assignment, $user, $kind, $trigger, $actorId, new ElternabfrageMail(
            kind: ElternabfrageMail::KIND_CONFIRMATION_REQUEST,
            userName: $user->name,
            inquiryTitle: $inquiry->title,
            childName: $assignment->childName(),
            deadline: $assignment->effectiveDeadline()->format('d.m.Y H:i'),
            url: $url,
        )) === null;
    }

    /**
     * Alle Sorgeberechtigten haben bestätigt: Hinweis an alle Beteiligten.
     */
    public function confirmationsComplete(InquiryAssignment $assignment): void
    {
        $inquiry = $assignment->inquiry;

        app(GuardianConfirmation::class)->guardians($assignment)->each(fn (User $user) => Notification::create([
            'type' => self::NOTIFICATION_TYPE,
            'user_id' => $user->id,
            'title' => 'Abfrage vollständig bestätigt',
            'message' => '„'.$inquiry->title.'“ für '.($assignment->child?->first_name ?? 'Ihr Kind').' wurde von allen Sorgeberechtigten bestätigt.',
            'url' => url('elternabfragen/'.$assignment->id),
            'icon' => self::ICON,
        ]));
    }

    /**
     * Neue Änderungsmeldung: Hinweis an die verantwortliche Person (Glocke inkl. Push).
     * Enthält keine Antwortinhalte.
     */
    public function changeRequested(InquiryChangeRequest $request): void
    {
        $assignment = $request->assignment;
        $inquiry = $assignment->inquiry;
        $userId = $inquiry->responsible_user_id ?? $inquiry->created_by;
        if (! $userId) {
            return;
        }

        Notification::create([
            'type' => self::NOTIFICATION_TYPE,
            'user_id' => $userId,
            'title' => 'Änderung zu einer Elternabfrage gemeldet',
            'message' => ($request->user?->name ?? 'Eine sorgeberechtigte Person').' hat eine Änderung zu „'.$inquiry->title.'“ für '.$assignment->childName().' gemeldet.',
            'url' => url('verwaltung/elternabfragen/aenderungen/'.$request->id),
            'icon' => 'fas fa-exchange-alt',
            'important' => true,
        ]);
        $this->log($assignment, $userId, InquiryDelivery::KIND_CHANGE_NOTICE, InquiryDelivery::CHANNEL_BELL, 'auto', null, true);
    }

    /**
     * Änderungsmeldung bearbeitet: Rückmeldung an die meldende Person. Nach Übernahme werden
     * weitere Sorgeberechtigte informiert bzw. – bei gemeinsamer Erklärung – um Bestätigung gebeten.
     */
    public function changeHandled(InquiryChangeRequest $request): void
    {
        $assignment = $request->assignment;
        $inquiry = $assignment->inquiry;
        $requester = $request->user;
        $accepted = $request->status === InquiryChangeRequestStatus::Accepted;
        $url = url('elternabfragen/'.$assignment->id);
        $child = $assignment->child?->first_name ?? $assignment->childName();

        if ($requester) {
            Notification::create([
                'type' => self::NOTIFICATION_TYPE,
                'user_id' => $requester->id,
                'title' => $accepted ? 'Änderung übernommen' : 'Änderung nicht übernommen',
                'message' => $accepted
                    ? 'Ihre Änderung zu „'.$inquiry->title.'“ für '.$child.' wurde übernommen.'
                    : 'Ihre Änderung zu „'.$inquiry->title.'“ für '.$child.' wurde nicht übernommen. Bitte beachten Sie die Begründung.',
                'url' => $url,
                'icon' => self::ICON,
                'important' => ! $accepted,
            ]);
            $this->log($assignment, $requester->id, InquiryDelivery::KIND_CHANGE_RESULT, InquiryDelivery::CHANNEL_BELL, 'auto', $request->handled_by, true);

            // Übernommene Änderung: Rückmeldung kommt mit der Quittung (sendReceipt), nicht doppelt
            if (filled($requester->email) && ! ($accepted && $request->response_id !== null && $this->hasReceipt($request->response_id))) {
                $this->sendMail($assignment, $requester, InquiryDelivery::KIND_CHANGE_RESULT, 'auto', $request->handled_by, new ElternabfrageMail(
                    kind: $accepted ? ElternabfrageMail::KIND_CHANGE_ACCEPTED : ElternabfrageMail::KIND_CHANGE_REJECTED,
                    userName: $requester->name,
                    inquiryTitle: $inquiry->title,
                    childName: $assignment->childName(),
                    deadline: $assignment->effectiveDeadline()->format('d.m.Y H:i'),
                    url: $url,
                    version: $request->response?->version,
                ));
            }
        }

        if (! $accepted) {
            return;
        }

        $confirmation = app(GuardianConfirmation::class);
        if ($confirmation->required($assignment)) {
            $confirmation->pendingUsers($assignment)
                ->each(fn (User $user) => $this->requestConfirmation($assignment, $user, 'auto', $request->handled_by, $requester));

            return;
        }

        $assignment->recipients()->with('user')->get()
            ->filter(fn (InquiryRecipient $r) => $r->user && $r->user_id !== $request->user_id)
            ->each(fn (InquiryRecipient $r) => Notification::create([
                'type' => self::NOTIFICATION_TYPE,
                'user_id' => $r->user_id,
                'title' => 'Angaben geändert',
                'message' => '„'.$inquiry->title.'“ für '.$child.': Eine von '.($requester?->name ?? 'einer sorgeberechtigten Person').' gemeldete Änderung wurde übernommen.',
                'url' => $url,
                'icon' => self::ICON,
            ]));
    }

    /**
     * Erklärungsquittung an die erklärende Person (Listener auf ReceiptIssued). Zugleich die
     * Eingangsbestätigung der Erklärung – es geht genau eine Mail je Erklärung.
     * Nicht sensibel: PDF im Anhang. Sensibel oder PDF nicht erzeugbar: nur Hinweis auf den
     * Download. Nachträglich erstellte Quittungen werden nie verschickt.
     */
    public function sendReceipt(InquiryReceipt $receipt): void
    {
        $user = $this->receiptRecipient($receipt);
        if ($user === null) {
            return;
        }

        $assignment = InquiryAssignment::with(['inquiry', 'child' => fn ($q) => $q->withTrashed()])->find($receipt->assignment_id);
        if ($assignment === null || $assignment->inquiry === null) {
            return;
        }

        $pdf = null;
        if (! $this->receiptIsSensitive($receipt)) {
            try {
                $pdf = app(ReceiptService::class)->pdf($receipt);
            } catch (Throwable $e) {
                // Mail trotzdem verschicken – mit Hinweis auf den Download
                report($e);
            }
        }

        [$mailKind, $deliveryKind] = match ($receipt->kind) {
            InquiryReceiptKind::ChangeAccepted => [ElternabfrageMail::KIND_CHANGE_ACCEPTED, InquiryDelivery::KIND_CHANGE_RESULT],
            InquiryReceiptKind::Confirmation => [ElternabfrageMail::KIND_CONFIRMED, InquiryDelivery::KIND_CONFIRMATION],
            InquiryReceiptKind::Withdrawal => [ElternabfrageMail::KIND_WITHDRAWAL, InquiryDelivery::KIND_WITHDRAWAL],
            default => [ElternabfrageMail::KIND_CONFIRMATION, InquiryDelivery::KIND_CONFIRMATION],
        };

        $this->sendMail($assignment, $user, $deliveryKind, 'auto', null, new ElternabfrageMail(
            kind: $mailKind,
            userName: $user->name,
            inquiryTitle: $assignment->inquiry->title,
            childName: $receipt->child_name,
            deadline: $assignment->effectiveDeadline()->format('d.m.Y H:i'),
            url: url('elternabfragen/'.$assignment->id),
            submittedAt: $receipt->declared_at->format('d.m.Y H:i'),
            version: $receipt->response_version,
            receiptNumber: $receipt->uuid,
            receiptPdf: $pdf,
            receiptFileName: $pdf !== null ? $receipt->fileName() : null,
        ));
    }

    /**
     * Wie erhält die erklärende Person ihre Quittung? „mail“ = PDF per E-Mail,
     * „download“ = sensible Abfrage, keine Mailadresse o. Ä. (nur Download in Portal/App).
     */
    public function receiptDelivery(InquiryReceipt $receipt): string
    {
        return $this->receiptRecipient($receipt) !== null && ! $this->receiptIsSensitive($receipt) ? 'mail' : 'download';
    }

    /**
     * Widerruf einer Einwilligung: Hinweis an die verantwortliche Person und an weitere
     * Sorgeberechtigte (ohne Antwortinhalte). Fehlende Bestätigungen der zugrunde liegenden
     * Version werden für die neue Version erneut angefragt.
     */
    public function withdrawn(InquiryResponse $response): void
    {
        $assignment = $response->assignment;
        $inquiry = $assignment->inquiry;
        $user = $response->user;
        $name = $user?->name ?? 'Eine sorgeberechtigte Person';
        $child = $assignment->child?->first_name ?? $assignment->childName();

        $responsibleId = $inquiry->responsible_user_id ?? $inquiry->created_by;
        if ($responsibleId) {
            Notification::create([
                'type' => self::NOTIFICATION_TYPE,
                'user_id' => $responsibleId,
                'title' => 'Einwilligung widerrufen',
                'message' => $name.' hat zu „'.$inquiry->title.'“ für '.$assignment->childName().' eine Einwilligung widerrufen.',
                'url' => url('verwaltung/elternabfragen/antworten/'.$response->id),
                'icon' => 'fas fa-user-slash',
                'important' => true,
            ]);
            $this->log($assignment, $responsibleId, InquiryDelivery::KIND_WITHDRAWAL_NOTICE, InquiryDelivery::CHANNEL_BELL, 'auto', null, true);
        }

        $assignment->recipients()->with('user')->get()
            ->filter(fn (InquiryRecipient $r) => $r->user && $r->user_id !== $response->user_id)
            ->each(fn (InquiryRecipient $r) => Notification::create([
                'type' => self::NOTIFICATION_TYPE,
                'user_id' => $r->user_id,
                'title' => 'Einwilligung widerrufen',
                'message' => '„'.$inquiry->title.'“ für '.$child.': '.$name.' hat eine Einwilligung widerrufen.',
                'url' => url('elternabfragen/'.$assignment->id),
                'icon' => self::ICON,
            ]));

        app(GuardianConfirmation::class)->pendingUsers($assignment)
            ->each(fn (User $pending) => $this->requestConfirmation($assignment, $pending, 'auto', null, $user));
    }

    /** Empfänger der Quittungsmail: erklärende Person mit E-Mail und Sorgerecht; nie bei Nacherfassung. */
    private function receiptRecipient(InquiryReceipt $receipt): ?User
    {
        if ($receipt->backfilled || $receipt->user_id === null) {
            return null;
        }
        $user = $receipt->user;
        $assignment = InquiryAssignment::with(['inquiry', 'child'])->find($receipt->assignment_id);
        if ($user === null || blank($user->email) || $assignment === null) {
            return null;
        }

        return app(InquiryAccess::class)->canAnswer($user, $assignment) ? $user : null;
    }

    private function receiptIsSensitive(InquiryReceipt $receipt): bool
    {
        return (bool) InquiryTemplateVersion::whereKey($receipt->template_version_id)->value('is_sensitive');
    }

    /** Zur Antwortversion gibt es eine (eigene, nicht nachträgliche) Quittung, deren Mail die Bestätigung ersetzt. */
    private function hasReceipt(int $responseId): bool
    {
        return InquiryReceipt::where('response_id', $responseId)->whereNull('confirmation_id')->where('backfilled', false)->exists();
    }

    /**
     * Hinweis an die verantwortliche Person bei technischen Zustellproblemen.
     */
    public function notifyProblems(Inquiry $inquiry, int $count): void
    {
        $userId = $inquiry->responsible_user_id ?? $inquiry->created_by;
        if ($count < 1 || ! $userId) {
            return;
        }

        Notification::create([
            'type' => self::NOTIFICATION_TYPE,
            'user_id' => $userId,
            'title' => 'Zustellprobleme bei einer Elternabfrage',
            'message' => '„'.$inquiry->title.'“: '.$count.' '.($count === 1 ? 'Kind ist' : 'Kinder sind').' nicht erreichbar. Bitte im Rücklauf prüfen.',
            'url' => url('verwaltung/elternabfragen/abfragen/'.$inquiry->id.'?status='.InquiryAssignmentStatus::Problem->value),
            'icon' => 'fas fa-exclamation-triangle',
            'important' => true,
        ]);

        InquiryDelivery::create([
            'inquiry_id' => $inquiry->id,
            'user_id' => $userId,
            'kind' => InquiryDelivery::KIND_PROBLEM_NOTICE,
            'channel' => InquiryDelivery::CHANNEL_BELL,
            'trigger' => 'auto',
            'success' => true,
            'sent_at' => now(),
        ]);
    }

    private function deliver(InquiryAssignment $assignment, string $kind, string $trigger, ?int $actorId): bool
    {
        $inquiry = $assignment->inquiry;
        $recipients = $assignment->recipients()->with('user')->get()->filter(fn (InquiryRecipient $r) => $r->user !== null);
        $isRelease = $kind === InquiryDelivery::KIND_RELEASE;

        if ($recipients->isEmpty()) {
            $this->markProblem($assignment, 'Kein sorgeberechtigtes Elternkonto hinterlegt.');

            return false;
        }

        $reachedAny = false;
        $errors = [];

        foreach ($recipients as $recipient) {
            $user = $recipient->user;
            $reached = false;
            $error = null;

            if ($inquiry->notify_push) {
                Notification::create([
                    'type' => self::NOTIFICATION_TYPE,
                    'user_id' => $user->id,
                    'title' => $isRelease ? 'Neue Elternabfrage' : 'Erinnerung: Elternabfrage',
                    'message' => '„'.$inquiry->title.'“ für '.($assignment->child?->first_name ?? 'Ihr Kind')
                        .' – bitte bis '.$assignment->effectiveDeadline()->format('d.m.Y').' ausfüllen.',
                    'url' => url('elternabfragen/'.$assignment->id),
                    'icon' => self::ICON,
                    'important' => ! $isRelease,
                ]);
                $this->log($assignment, $user->id, $kind, InquiryDelivery::CHANNEL_BELL, $trigger, $actorId, true);
                $reached = true;
            }

            if ($inquiry->notify_mail) {
                if (blank($user->email)) {
                    $error = 'Keine E-Mail-Adresse hinterlegt.';
                    $this->log($assignment, $user->id, $kind, InquiryDelivery::CHANNEL_MAIL, $trigger, $actorId, false, $error);
                } else {
                    $error = $this->sendMail($assignment, $user, $kind, $trigger, $actorId, new ElternabfrageMail(
                        kind: $isRelease ? ElternabfrageMail::KIND_RELEASE : ElternabfrageMail::KIND_REMINDER,
                        userName: $user->name,
                        inquiryTitle: $inquiry->title,
                        childName: $assignment->childName(),
                        deadline: $assignment->effectiveDeadline()->format('d.m.Y H:i'),
                        url: url('elternabfragen/'.$assignment->id),
                        purpose: $isRelease ? $inquiry->templateVersion?->purpose : null,
                    ));
                    $reached = $reached || $error === null;
                }
            }

            $recipient->forceFill([
                'notified_at' => $isRelease && $reached ? now() : $recipient->notified_at,
                'delivery_failed_at' => $reached ? null : now(),
                'delivery_error' => $reached ? null : ($error ?? 'Kein Benachrichtigungskanal aktiv.'),
            ])->save();

            $reachedAny = $reachedAny || $reached;
            if (! $reached) {
                $errors[] = $user->name.': '.($error ?? 'nicht erreichbar');
            }
        }

        if ($isRelease) {
            $assignment->notified_at = $reachedAny ? now() : null;
            $assignment->save();
        }

        if (! $reachedAny) {
            $this->markProblem($assignment, 'Zustellung nicht möglich – '.implode('; ', $errors));
        }

        return $reachedAny;
    }

    private function sendMail(InquiryAssignment $assignment, User $user, string $kind, string $trigger, ?int $actorId, ElternabfrageMail $mail): ?string
    {
        try {
            Mail::to($user->email)->send($mail);
            $this->log($assignment, $user->id, $kind, InquiryDelivery::CHANNEL_MAIL, $trigger, $actorId, true);

            return null;
        } catch (Throwable $e) {
            $error = Str::limit('E-Mail-Versand fehlgeschlagen: '.$e->getMessage(), 250);
            report($e);
            $this->log($assignment, $user->id, $kind, InquiryDelivery::CHANNEL_MAIL, $trigger, $actorId, false, $error);

            return $error;
        }
    }

    private function markProblem(InquiryAssignment $assignment, string $note): void
    {
        $assignment->has_problem = true;
        $assignment->problem_note = Str::limit($note, 1000);
        $assignment->status = $assignment->recomputeStatus();
        $assignment->save();
    }

    private function log(InquiryAssignment $assignment, ?int $userId, string $kind, string $channel, string $trigger, ?int $actorId, bool $success, ?string $error = null): void
    {
        InquiryDelivery::create([
            'inquiry_id' => $assignment->inquiry_id,
            'assignment_id' => $assignment->id,
            'user_id' => $userId,
            'kind' => $kind,
            'channel' => $channel,
            'trigger' => $trigger,
            'triggered_by' => $actorId,
            'success' => $success,
            'error' => $error,
            'sent_at' => now(),
        ]);
    }
}
