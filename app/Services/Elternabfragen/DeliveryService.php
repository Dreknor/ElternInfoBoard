<?php

namespace App\Services\Elternabfragen;

use App\Enums\InquiryAssignmentStatus;
use App\Mail\ElternabfrageMail;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Model\InquiryDelivery;
use App\Model\InquiryRecipient;
use App\Model\InquiryResponse;
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
            return true;
        }

        return $this->deliver($assignment, $kind, $trigger, $actorId);
    }

    /**
     * Eingangsbestätigung an die absendende Person; weitere Sorgeberechtigte werden
     * informiert, dass die Abfrage beantwortet wurde (getrennte Haushalte).
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

            if (filled($submitter->email)) {
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
