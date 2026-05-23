<?php

namespace App\Console\Commands;

use App\Model\Child;
use App\Model\UcsLinkCandidate;
use App\Services\Ucs\LinkCandidateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Verknüpft ein lokales Kind mit einem UCS-Account per CLI.
 *
 * Schreibt children.ucs_username (und ggf. ucs_uuid) auf den lokalen Datensatz
 * und markiert den passenden ucs_link_candidates-Eintrag als bestätigt.
 * Nutzt LinkCandidateService::confirm() – identische Logik wie das Admin-UI.
 *
 * Validierungen:
 *   – Kind muss in der lokalen DB existieren
 *   – Ist ucs_username bereits identisch → idempotente Meldung, Exit 0
 *   – Hat das Kind bereits einen anderen ucs_username → Fehler, Exit 1
 *   – ucs_username darf nicht bereits von einem anderen Kind belegt sein
 *
 * Idempotent: Zweimaliger Aufruf ändert nichts, Exit 0 mit Hinweis-Message.
 *
 * Exit-Codes:
 *   0 – Erfolgreich (oder bereits verknüpft)
 *   1 – Validierungsfehler
 *
 * @see docs/ucs-kelvin-integration-konzept.md §8
 * @see App\Services\Ucs\LinkCandidateService
 */
class UcsLinkChild extends Command
{
    protected $signature = 'ucs:link-child
        {child_id : ID des lokalen Kindes in der DB}
        {ucs_username : UCS-Username des zu verlinkenden Kindes}';

    protected $description = 'Verknüpft ein lokales Kind mit einem UCS-Account (ucs_username setzen).';

    public function handle(LinkCandidateService $service): int
    {
        $childId     = (int) $this->argument('child_id');
        $ucsUsername = trim((string) $this->argument('ucs_username'));

        // ── Validierung: Kind in DB vorhanden? ────────────────────────────────
        /** @var Child|null $child */
        $child = Child::withoutGlobalScopes()->find($childId);

        if ($child === null) {
            $this->error("Kind mit ID {$childId} nicht gefunden.");

            return self::FAILURE;
        }

        // ── Idempotenz-Check: bereits mit demselben Username verknüpft? ───────
        if ($child->ucs_username === $ucsUsername) {
            $this->info("Kind {$childId} ist bereits mit ucs_username='{$ucsUsername}' verknüpft. Keine Änderung.");
            Log::channel('ucs')->info('[ucs:link-child] Bereits verknüpft – idempotent.', [
                'child_id'     => $childId,
                'ucs_username' => $ucsUsername,
            ]);

            return self::SUCCESS;
        }

        // ── Validierung: ucs_username noch leer? ──────────────────────────────
        if (! empty($child->ucs_username)) {
            $this->error(
                "Kind {$childId} hat bereits ucs_username='{$child->ucs_username}'."
                ." Zum Überschreiben bitte direkt in der DB korrigieren."
            );

            return self::FAILURE;
        }

        // ── Validierung: Username nicht bereits bei anderem Kind belegt? ───────
        $conflict = Child::withoutGlobalScopes()
            ->where('ucs_username', $ucsUsername)
            ->where('id', '!=', $childId)
            ->first();

        if ($conflict !== null) {
            $this->error(
                "ucs_username='{$ucsUsername}' ist bereits bei Kind ID {$conflict->id} vergeben."
            );

            return self::FAILURE;
        }

        // ── Kandidaten suchen oder manuell anlegen ───────────────────────────
        /** @var UcsLinkCandidate $candidate */
        $candidate = UcsLinkCandidate::where('child_id', $childId)
            ->where('ucs_username', $ucsUsername)
            ->first();

        if ($candidate === null) {
            // Kein automatisch erkannter Kandidat vorhanden → manuellen anlegen
            $candidate = UcsLinkCandidate::create([
                'child_id'     => $childId,
                'ucs_username' => $ucsUsername,
                'reason'       => 'manual',
                'detected_at'  => now(),
            ]);
            $this->line("  ↳ Manueller Link-Kandidat angelegt (ID {$candidate->id}).");
        } else {
            $this->line("  ↳ Vorhandener Kandidat gefunden (ID {$candidate->id}, reason={$candidate->reason}).");
        }

        // ── Linking via Service (gemeinsame Logik mit UI) ─────────────────────
        try {
            $child = $service->confirm($candidate, null /* CLI hat keinen User-Kontext */);
        } catch (\Throwable $e) {
            $this->error('Fehler beim Verknüpfen: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            "✓ Kind %d (%s %s) erfolgreich mit ucs_username='%s' verknüpft.",
            $childId,
            $child->first_name,
            $child->last_name,
            $ucsUsername,
        ));

        return self::SUCCESS;
    }
}
