<?php

namespace App\Services\Ucs;

use App\Model\Child;
use App\Model\UcsLinkCandidate;
use App\Model\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kapselt die Confirm-/Reject-Logik für UCS-Link-Kandidaten.
 *
 * Wird gemeinsam genutzt von:
 *   - UcsLinkCandidateController (Admin-UI)
 *   - UcsLinkChild-Artisan-Command (CLI)
 *
 * Idempotenz-Garantien:
 *   - confirm() auf bereits bestätigten Kandidaten → no-op, gibt Child zurück.
 *   - reject() auf bereits verarbeitetem Kandidaten → no-op, kein Fehler.
 *
 * @see docs/todos/08-initial-linking-workflow.md
 * @see docs/ucs-kelvin-integration-konzept.md §5.2 / §8
 */
class LinkCandidateService
{
    /**
     * Bestätigt einen Link-Kandidaten und verknüpft das lokale Kind mit dem UCS-Account.
     *
     * Ablauf:
     *   1. Idempotenz-Check: bereits confirmed → no-op
     *   2. Konflikt-Check: ucs_username bereits bei anderem Kind vergeben → Exception
     *   3. ucs_username (+ ggf. ucs_uuid) auf das Kind schreiben
     *   4. Kandidat als bestätigt markieren
     *
     * ucs_source bleibt bewusst unverändert ('local'), da es sich um manuell
     * gepflegte Stammdaten handelt.
     *
     * @throws \RuntimeException Wenn ucs_username bereits bei einem anderen Kind vergeben ist.
     * @return Child Das aktualisierte Kind-Modell (fresh reload).
     */
    public function confirm(UcsLinkCandidate $candidate, ?User $by = null): Child
    {
        return DB::transaction(function () use ($candidate, $by) {
            // Idempotenz: bereits confirmed (auch rejected) → no-op
            if ($candidate->confirmed_at !== null) {
                $this->log('info', 'Kandidat bereits bestätigt (no-op).', [
                    'candidate_id' => $candidate->id,
                    'child_id'     => $candidate->child_id,
                ]);

                return $candidate->child;
            }

            /** @var Child $child */
            $child = Child::withoutGlobalScopes()->findOrFail($candidate->child_id);

            // Konflikt-Check: ucs_username nicht bereits bei einem anderen Kind vergeben?
            $conflict = Child::withoutGlobalScopes()
                ->where('ucs_username', $candidate->ucs_username)
                ->where('id', '!=', $child->id)
                ->first();

            if ($conflict !== null) {
                throw new \RuntimeException(
                    "ucs_username='{$candidate->ucs_username}' ist bereits bei Kind ID {$conflict->id} vergeben."
                );
            }

            // ucs_source bleibt 'local' (manuelle Pflege respektiert!)
            $updates = ['ucs_username' => $candidate->ucs_username];
            if (! empty($candidate->ucs_uuid)) {
                $updates['ucs_uuid'] = $candidate->ucs_uuid;
            }

            $child->update($updates);

            $candidate->update([
                'confirmed_at' => now(),
                'confirmed_by' => $by?->id,
            ]);

            $this->log('info', 'Kind erfolgreich verknüpft.', [
                'candidate_id' => $candidate->id,
                'child_id'     => $child->id,
                'ucs_username' => $candidate->ucs_username,
                'ucs_uuid'     => $candidate->ucs_uuid,
                'confirmed_by' => $by?->id,
            ]);

            return $child->fresh();
        });
    }

    /**
     * Verwirft einen Link-Kandidaten.
     *
     * Setzt payload.status = 'rejected' + confirmed_at, damit der nächste
     * Sync-Lauf denselben Kandidaten NICHT erneut detektiert (firstOrCreate
     * findet den vorhandenen Eintrag).
     *
     * Idempotent: Bereits verarbeitete Kandidaten werden übersprungen.
     */
    public function reject(UcsLinkCandidate $candidate, ?User $by = null, string $note = ''): void
    {
        if ($candidate->confirmed_at !== null) {
            $this->log('info', 'Kandidat bereits verarbeitet – reject ignoriert.', [
                'candidate_id' => $candidate->id,
            ]);

            return;
        }

        $payload              = $candidate->payload ?? [];
        $payload['status']    = 'rejected';
        if ($note !== '') {
            $payload['rejected_note'] = $note;
        }
        if ($by !== null) {
            $payload['rejected_by_name'] = $by->name;
        }

        $candidate->update([
            'confirmed_at' => now(),
            'confirmed_by' => $by?->id,
            'payload'      => $payload,
        ]);

        $this->log('info', 'Kandidat verworfen.', [
            'candidate_id' => $candidate->id,
            'child_id'     => $candidate->child_id,
            'ucs_username' => $candidate->ucs_username,
            'rejected_by'  => $by?->id,
            'note'         => $note,
        ]);
    }

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context = []): void
    {
        Log::channel('ucs')->{$level}('[LinkCandidateService] '.$message, $context);
    }
}

