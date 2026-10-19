<?php

namespace App\Services\Elternabfragen;

use App\Enums\GuardianRight;
use App\Model\InquiryAssignment;
use App\Model\InquiryConfirmation;
use App\Model\InquiryEvent;
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
 */
class GuardianConfirmation
{
    public function __construct(private readonly FamilyResolver $family) {}

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

        $response = $this->currentResponse($assignment);
        $done = InquiryConfirmation::where('response_id', $response->id)->pluck('user_id')->push($response->user_id)->filter()->all();

        return $this->guardians($assignment)->reject(fn (User $user) => in_array($user->id, $done, true))->values();
    }

    public function isPending(InquiryAssignment $assignment): bool
    {
        return $this->pendingUsers($assignment)->isNotEmpty();
    }

    public function canConfirm(User $user, InquiryAssignment $assignment): bool
    {
        return $assignment->inquiry?->isRunning()
            && $this->pendingUsers($assignment)->contains('id', $user->id);
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

        $response = $this->currentResponse($assignment);
        $confirmations = InquiryConfirmation::where('response_id', $response->id)->get()->keyBy('user_id');
        $rows = [];

        foreach ($this->guardians($assignment)->push($response->user)->filter()->unique('id') as $user) {
            $confirmation = $confirmations->get($user->id);
            $rows[] = [
                'name' => $user->name,
                'user_id' => $user->id,
                'state' => match (true) {
                    $user->id === $response->user_id => 'submitted',
                    $confirmation !== null => 'confirmed',
                    default => 'pending',
                },
                'at' => $user->id === $response->user_id ? $response->submitted_at : $confirmation?->confirmed_at,
            ];
        }

        // Bestätigungen von Personen, die inzwischen kein Sorgerecht mehr haben, bleiben sichtbar
        foreach ($confirmations->except(array_column($rows, 'user_id')) as $confirmation) {
            $rows[] = ['name' => $confirmation->user?->name ?? 'unbekannt', 'user_id' => (int) $confirmation->user_id, 'state' => 'confirmed', 'at' => $confirmation->confirmed_at];
        }

        return $rows;
    }

    /**
     * Gültige Antwortversion verbindlich bestätigen.
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

            return $confirmation;
        });

        $assignment->refresh();
        if (! $this->isPending($assignment)) {
            app(DeliveryService::class)->confirmationsComplete($assignment);
        }

        return $confirmation;
    }

    private function currentResponse(InquiryAssignment $assignment): InquiryResponse
    {
        $loaded = $assignment->relationLoaded('currentResponse') ? $assignment->currentResponse : null;

        return $loaded && $loaded->id === $assignment->current_response_id
            ? $loaded
            : InquiryResponse::with('user')->findOrFail($assignment->current_response_id);
    }
}
