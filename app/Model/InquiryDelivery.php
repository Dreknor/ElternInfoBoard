<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Protokoll jeder Benachrichtigung und Erinnerung: Zeitpunkt, Kanal, Empfänger, Ergebnis.
 */
class InquiryDelivery extends Model
{
    public const KIND_RELEASE = 'release';

    public const KIND_REMINDER_BEFORE = 'reminder_before';

    public const KIND_REMINDER_AFTER = 'reminder_after';

    public const KIND_REMINDER_MANUAL = 'reminder_manual';

    public const KIND_CONFIRMATION = 'confirmation';

    public const KIND_PROBLEM_NOTICE = 'problem_notice';

    /** Bitte an weitere Sorgeberechtigte, die eingereichte Antwort zu bestätigen. */
    public const KIND_CONFIRMATION_REQUEST = 'confirmation_request';

    /** Hinweis an die verantwortliche Person: Eltern haben eine Änderung gemeldet. */
    public const KIND_CHANGE_NOTICE = 'change_notice';

    /** Rückmeldung an die Eltern: Änderungsmeldung übernommen bzw. abgelehnt. */
    public const KIND_CHANGE_RESULT = 'change_result';

    /** Hinweis an die verantwortliche Person: Eltern haben eine Einwilligung widerrufen. */
    public const KIND_WITHDRAWAL_NOTICE = 'withdrawal_notice';

    /** Quittung über einen Widerruf an die widerrufende Person. */
    public const KIND_WITHDRAWAL = 'withdrawal';

    public const REMINDER_KINDS = [self::KIND_REMINDER_BEFORE, self::KIND_REMINDER_AFTER, self::KIND_REMINDER_MANUAL];

    public const CHANNEL_MAIL = 'mail';

    public const CHANNEL_BELL = 'bell';

    public $timestamps = false;

    protected $fillable = [
        'inquiry_id', 'assignment_id', 'user_id', 'kind', 'channel', 'trigger', 'triggered_by', 'success', 'error', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(InquiryAssignment::class, 'assignment_id');
    }

    public function kindLabel(): string
    {
        return match ($this->kind) {
            self::KIND_RELEASE => 'Benachrichtigung',
            self::KIND_REMINDER_BEFORE => 'Erinnerung vor Fristende',
            self::KIND_REMINDER_AFTER => 'Erinnerung (Nachfrist)',
            self::KIND_REMINDER_MANUAL => 'Erinnerung',
            self::KIND_CONFIRMATION => 'Eingangsbestätigung',
            self::KIND_PROBLEM_NOTICE => 'Hinweis Zustellproblem',
            self::KIND_CONFIRMATION_REQUEST => 'Bitte um Bestätigung',
            self::KIND_CHANGE_NOTICE => 'Hinweis Änderungsmeldung',
            self::KIND_CHANGE_RESULT => 'Rückmeldung zur Änderungsmeldung',
            self::KIND_WITHDRAWAL_NOTICE => 'Hinweis Widerruf',
            self::KIND_WITHDRAWAL => 'Bestätigung des Widerrufs',
            default => $this->kind,
        };
    }

    public function channelLabel(): string
    {
        return $this->channel === self::CHANNEL_MAIL ? 'E-Mail' : 'App/Web-Push';
    }
}
