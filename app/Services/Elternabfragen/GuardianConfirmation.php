<?php

namespace App\Services\Elternabfragen;

use App\Enums\GuardianRight;
use App\Enums\InquiryReceiptKind;
use App\Model\InquiryAssignment;
use App\Model\InquiryChangeRequest;
use App\Model\InquiryConfirmation;
use App\Model\InquiryEvent;
use App\Model\InquiryReceipt;
use App\Model\InquiryResponse;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Bestätigung durch alle Sorgeberechtigten (Option der Vorlage „requires_all_guardians“).
 *
 * Eine sorgeberechtigte Person reicht die Antwort ein, alle weiteren Sorgeberechtigten
 * bestätigen genau diese Antwortversion. Erst dann gilt der Auftrag als abgeschlossen.
 * Eine Korrektur erzeugt eine neue Version und erfordert erneut alle Bestätigungen.
 * Maßgeblich sind die aktuell gültigen Sorgerechtsbeziehungen zum Kind.
 *
 * Widerruf einer Einwilligung (Art. 7 Abs. 3 DSGVO): Er wirkt sofort und braucht keine
 * erneute Bestätigung. Eine Widerrufsversion übernimmt daher den Bestätigungsstand der
 * Version, auf der sie beruht (Kette über previous_response_id).
 */
class GuardianConfirmation
{
    public function __construct(
        private readonly FamilyResolver $family,
        private readonly ReceiptService $receipts,
    ) {}

    public function required(InquiryAssignment $assignment): bool
    {
        return (bool) $assignment->inquiry?->templateVersion?->requires_all_guardians;
    }

    /**
     * Alle Konten mit gültigem Sorgerecht für das Kind.
     *
     * @return Collection<int, User>
     */
    public function guardians(InquiryAssignment $assignment): Collection
    {
        $child = $assignment->child;
        if ($child === null) {
            return collect();
        }

        return $this->family->guardiansFor($child, GuardianRight::Custody)
            ->unique('id')
            ->filter(fn (User $user) => $this->family->hasAccessToChild($user, $child, GuardianRight::Custody))
            ->sortBy('name')
            ->values()
            ->toBase();
    }

    /**
     * Sorgeberechtigte, die die gültige Antwort noch bestätigen müssen.
     *
     * @return Collection<int, User>
     */
    public function pendingUsers(InquiryAssignment $assignment): Collection
    {
        if (! $this->required($assignment) || $assignment->current_response_id === null) {
            return collect();
        }

        $chain = $this->chain($this->currentResponse($assignment));
        $done = InquiryConfirmation::whereIn('response_id', $chain->pluck('id'))->pluck('user_id')
            ->merge($chain->pluck('user_id'))->filter()->all();

        return $this->guardians($assignment)->reject(fn (User $user) => in_array($user->id, $done, true))->values();
    }

    public function isPending(InquiryAssignment $assignment): bool
    {
        return $this->pendingUsers($assignment)->isNotEmpty();
    }

    public function canConfirm(User $user, InquiryAssignment $assignment): bool
    {
        return ($assignment->acceptsAnswers() || $this->fromChangeRequest($assignment))
            && $this->pendingUsers($assignment)->contains('id', $user->id);
    }

    /**
     * Die gültige Version stammt aus einer übernommenen Änderungsmeldung: Bestätigungen sind
     * dann auch nach Abschluss der Abfrage möglich (solange die Antworten nicht gelöscht sind).
     */
    public function fromChangeRequest(InquiryAssignment $assignment): bool
    {
        return $assignment->current_response_id !== null
            && $assignment->inquiry?->purged_at === null
            && InquiryChangeRequest::where('response_id', $assignment->current_response_id)->exists();
    }

    /**
     * Übersicht für Eltern- und Verwaltungsansicht: wer hat eingereicht, wer bestätigt, wer fehlt.
     *
     * @return list<array{name: string, user_id: int, state: string, at: ?\Carbon\CarbonInterface}>
     */
    public function overview(InquiryAssignment $assignment): array
    {
        if (! $this->required($assignment) || $assignment->current_response_id === null) {
            return [];
        }

        $chain = $this->chain($this->currentResponse($assignment));
        // Je Person die neueste Bestätigung bzw. die neueste eingereichte Version der Kette
        $confirmations = InquiryConfirmation::whereIn('response_id', $chain->pluck('id'))->orderBy('confirmed_at')->orderBy('id')->get()->keyBy('user_id')
            // Basis-Collection: except() arbeitet dann mit den Schlüsseln (user_id), nicht mit Modell-IDs
            ->toBase();
        $submitted = $chain->reverse()->keyBy('user_id');

        $rows = [];

        $users = $this->guardians($assignment);
        foreach ($chain as $version) {
            $users->push($version->user);
        }
        foreach ($users->filter()->unique('id') as $user) {
            $confirmation = $confirmations->get($user->id);
            $own = $submitted->get($user->id);
            $rows[] = [
                'name' => $user->name,
                'user_id' => $user->id,
                'state' => match (true) {
                    $own !== null => 'submitted',
                    $confirmation !== null => 'confirmed',
                    default => 'pending',
                },
                'at' => $own !== null ? $own->submitted_at : $confirmation?->confirmed_at,
            ];
        }

        // Bestätigungen von Personen, die inzwischen kein Sorgerecht mehr haben, bleiben sichtbar
        foreach ($confirmations->except(array_column($rows, 'user_id')) as $confirmation) {
            $rows[] = ['name' => $confirmation->user?->name ?? 'unbekannt', 'user_id' => (int) $confirmation->user_id, 'state' => 'confirmed', 'at' => $confirmation->confirmed_at];
        }

        return $rows;
    }

    /**
     * Gültige Antwortversion verbindlich bestätigen. Stellt in derselben Transaktion die
     * Erklärungsquittung aus (Relation „receipt“ der Rückgabe).
     *
     * @param  array{channel?: string, ip?: ?string, user_agent?: ?string}  $meta
     */
    public function confirm(InquiryAssignment $assignment, User $user, array $meta = []): InquiryConfirmation
    {
        $confirmation = DB::transaction(function () use ($assignment, $user, $meta) {
            /** @var InquiryAssignment $locked */
            $locked = InquiryAssignment::query()->whereKey($assignment->id)->lockForUpdate()->first();
            $locked->setRelation('inquiry', $assignment->inquiry);
            $locked->setRelation('child', $assignment->child);

            if (! $this->canConfirm($user, $locked)) {
                throw new HttpException(409, 'Eine Bestätigung ist nicht (mehr) möglich – möglicherweise wurde die Antwort inzwischen geändert.');
            }

            $confirmation = InquiryConfirmation::create([
                'response_id' => $locked->current_response_id,
                'assignment_id' => $locked->id,
                'user_id' => $user->id,
                'channel' => in_array($meta['channel'] ?? null, ['web', 'app'], true) ? $meta['channel'] : 'web',
                'ip_anonymized' => ResponseService::anonymizeIp($meta['ip'] ?? null),
                'user_agent' => isset($meta['user_agent']) ? Str::limit((string) $meta['user_agent'], 250, '') : null,
                'confirmed_at' => now(),
            ]);

            InquiryEvent::create([
                'inquiry_id' => $locked->inquiry_id,
                'assignment_id' => $locked->id,
                'user_id' => $user->id,
                'action' => 'response.confirmed',
                'data' => ['version' => (int) InquiryResponse::whereKey($locked->current_response_id)->value('version')],
            ]);

            $locked->refreshStatus();

            $response = InquiryResponse::findOrFail($locked->current_response_id);
            $confirmation->setRelation('receipt', $this->receipts->issue($response, InquiryReceiptKind::Confirmation, $confirmation, ['declarant' => $user]));

            return $confirmation;
        });

        $assignment->refresh();
        if (! $this->isPending($assignment)) {
            app(DeliveryService::class)->confirmationsComplete($assignment);
        }

        return $confirmation;
    }

    /**
     * Gültige Antwort und – solange sie aus einem Widerruf stammt – die Versionen, auf denen sie
     * beruht (neueste zuerst). Ohne Widerruf nur die gültige Antwort.
     *
     * @return Collection<int, InquiryResponse>
     */
    private function chain(InquiryResponse $response): Collection
    {
        $chain = collect([$response]);
        while ($response->previous_response_id !== null && $chain->count() < 50 && $this->isWithdrawal($response)) {
            $response = InquiryResponse::with('user')->find($response->previous_response_id);
            if ($response === null) {
                break;
            }
            $chain->push($response);
        }

        return $chain;
    }

    /** Die Antwortversion entstand durch einen Widerruf (maßgeblich ist ihre Quittung). */
    public function isWithdrawal(InquiryResponse $response): bool
    {
        return InquiryReceipt::where('response_id', $response->id)->where('kind', InquiryReceiptKind::Withdrawal->value)->exists();
    }

    private function currentResponse(InquiryAssignment $assignment): InquiryResponse
    {
        $loaded = $assignment->relationLoaded('currentResponse') ? $assignment->currentResponse : null;

        return $loaded && $loaded->id === $assignment->current_response_id
            ? $loaded
            : InquiryResponse::with('user')->findOrFail($assignment->current_response_id);
    }
}
