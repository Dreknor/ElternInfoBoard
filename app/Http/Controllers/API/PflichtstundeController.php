<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatePflichtstundeRequest;
use App\Http\Requests\UpdatePflichtstundeRequest;
use App\Http\Resources\PflichtstundeResource;
use App\Http\Resources\PflichtstundeStatsResource;
use App\Model\Pflichtstunde;
use App\Services\PflichtstundenFamilyService;
use App\Settings\PflichtstundenSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/**
 * Class PflichtstundeController
 *
 * API Controller für die Verwaltung von Pflichtstunden.
 * Ermöglicht Familien, ihre Pflichtstunden zu verfolgen und neue anzulegen.
 *
 * @group Pflichtstunden
 */
class PflichtstundeController extends Controller implements HasMiddleware
{
    protected PflichtstundenSetting $pflichtstunden_settings;

    protected PflichtstundenFamilyService $familyService;

    public function __construct()
    {
        $this->pflichtstunden_settings = new PflichtstundenSetting;
        $this->familyService = new PflichtstundenFamilyService($this->pflichtstunden_settings);
    }

    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * Liste aller Pflichtstunden der Familie
     *
     * Gibt alle Pflichtstunden des angemeldeten Users und seines Partners (sorg2) zurück,
     * sortiert nach Start-Datum absteigend. Die Pflichtstunden werden automatisch auf den
     * aktuellen Zeitraum gefiltert.
     *
     * @authenticated
     *
     * @responseField data array Liste aller Pflichtstunden der Familie
     * @responseField data[].id integer ID der Pflichtstunde. Example: 1
     * @responseField data[].user_id integer ID des Users. Example: 42
     * @responseField data[].user_name string Name des Users. Example: Max Mustermann
     * @responseField data[].start string Start-Zeitpunkt im ISO 8601 Format. Example: 2024-03-15T09:00:00.000000Z
     * @responseField data[].end string End-Zeitpunkt im ISO 8601 Format. Example: 2024-03-15T12:00:00.000000Z
     * @responseField data[].duration_minutes integer Dauer in Minuten. Example: 180
     * @responseField data[].duration_hours float Dauer in Stunden. Example: 3.0
     * @responseField data[].description string Beschreibung der Tätigkeit. Example: Schulhof säubern
     * @responseField data[].bereich string Bereich der Pflichtstunde. Example: Schulhof
     * @responseField data[].status string Status (upcoming, pending, approved, rejected). Example: approved
     * @responseField data[].approved boolean Ob die Pflichtstunde genehmigt wurde. Example: true
     * @responseField data[].approved_at string Zeitpunkt der Genehmigung. Example: 2024-03-16T10:00:00.000000Z
     * @responseField data[].approved_by integer ID des Genehmigers. Example: 1
     * @responseField data[].approver_name string Name des Genehmigers. Example: Admin
     * @responseField data[].rejected boolean Ob die Pflichtstunde abgelehnt wurde. Example: false
     * @responseField data[].rejected_at string Zeitpunkt der Ablehnung
     * @responseField data[].rejected_by integer ID des Ablehnenden
     * @responseField data[].rejector_name string Name des Ablehnenden
     * @responseField data[].rejection_reason string Grund für die Ablehnung
     * @responseField data[].listen_termin_id integer ID des verknüpften Listen-Termins
     * @responseField data[].created_at string Erstellungszeitpunkt. Example: 2024-03-15T08:00:00.000000Z
     * @responseField data[].updated_at string Änderungszeitpunkt. Example: 2024-03-16T10:00:00.000000Z
     * @responseField settings object Globale Pflichtstunden-Einstellungen
     * @responseField settings.required_hours integer Erforderliche Anzahl Stunden. Example: 20
     * @responseField settings.price_per_hour float Preis pro Stunde in Euro. Example: 15.0
     * @responseField settings.period_start string Start des Zeitraums (MM-DD Format). Example: 08-01
     * @responseField settings.period_end string Ende des Zeitraums (MM-DD Format). Example: 07-31
     *
     * @response 200 scenario="Erfolgreich" {
     *   "data": [
     *     {
     *       "id": 1,
     *       "user_id": 42,
     *       "user_name": "Max Mustermann",
     *       "start": "2024-03-15T09:00:00.000000Z",
     *       "end": "2024-03-15T12:00:00.000000Z",
     *       "duration_minutes": 180,
     *       "duration_hours": 3,
     *       "description": "Schulhof säubern",
     *       "bereich": "Schulhof",
     *       "status": "approved",
     *       "approved": true,
     *       "approved_at": "2024-03-16T10:00:00.000000Z",
     *       "approved_by": 1,
     *       "approver_name": "Admin",
     *       "rejected": false,
     *       "rejected_at": null,
     *       "rejected_by": null,
     *       "rejector_name": null,
     *       "rejection_reason": null,
     *       "listen_termin_id": null,
     *       "created_at": "2024-03-15T08:00:00.000000Z",
     *       "updated_at": "2024-03-16T10:00:00.000000Z"
     *     }
     *   ],
     *   "settings": {
     *     "required_hours": 20,
     *     "price_per_hour": 15,
     *     "period_start": "08-01",
     *     "period_end": "07-31"
     *   }
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "message": "Berechtigung fehlt"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->can('view Pflichtstunden')) {
            return response()->json(['message' => 'Berechtigung fehlt'], 403);
        }

        [$periodStart, $periodEnd] = $this->familyService->resolvePeriod(null);
        $familyUserIds = array_filter([$user->id, $user->sorg2]);
        $pflichtstunden = Pflichtstunde::withoutGlobalScope('aktuellerZeitraum')
            ->whereIn('user_id', $familyUserIds)
            ->whereBetween('start', [$periodStart, $periodEnd])
            ->orderBy('start', 'desc')
            ->get();

        $summaries = $this->familyService->buildFamilySummaries($periodStart, $periodEnd, true);
        $currentSummary = $summaries->first(fn (array $summary) => in_array($user->id, $summary['user_ids']));

        return response()->json([
            'data' => PflichtstundeResource::collection($pflichtstunden),
            'settings' => [
                'required_hours' => $currentSummary['required_hours'] ?? $this->pflichtstunden_settings->pflichtstunden_anzahl,
                'price_per_hour' => $currentSummary['hourly_rate'] ?? $this->pflichtstunden_settings->pflichtstunden_betrag,
                'global_required_hours' => $this->pflichtstunden_settings->pflichtstunden_anzahl,
                'global_reduced_required_hours' => $this->pflichtstunden_settings->pflichtstunden_anzahl_ermaessigt,
                'global_price_per_hour' => $this->pflichtstunden_settings->pflichtstunden_betrag,
                'global_reduced_price_per_hour' => $this->pflichtstunden_settings->pflichtstunden_betrag_ermaessigt,
                'period_start' => $this->pflichtstunden_settings->pflichtstunden_start,
                'period_end' => $this->pflichtstunden_settings->pflichtstunden_ende,
                'carryover_enabled' => $this->pflichtstunden_settings->konto_uebertrag_aktiv,
                'family_mode' => $currentSummary['rule_mode'] ?? 'standard',
            ],
        ]);
    }

    /**
     * Statistiken für die Familie
     *
     * Gibt detaillierte Statistiken über die Pflichtstunden der Familie zurück.
     * Beinhaltet Fortschrittsanzeige, Ranking im Vergleich zu anderen Familien,
     * und Berechnung des noch zu zahlenden Betrags.
     *
     * @authenticated
     *
     * @responseField progress object Fortschrittsinformationen
     * @responseField progress.percent float Prozentuale Erfüllung (0-100). Example: 75.5
     * @responseField progress.total_minutes_completed integer Geleistete Minuten. Example: 906
     * @responseField progress.total_hours_completed float Geleistete Stunden. Example: 15.1
     * @responseField progress.required_minutes integer Erforderliche Minuten. Example: 1200
     * @responseField progress.required_hours integer Erforderliche Stunden. Example: 20
     * @responseField progress.open_minutes integer Noch zu leistende Minuten. Example: 294
     * @responseField progress.open_hours float Noch zu leistende Stunden. Example: 4.9
     * @responseField progress.is_completed boolean Ob alle Pflichtstunden erfüllt sind. Example: false
     * @responseField ranking object Ranking-Informationen
     * @responseField ranking.your_rank integer Position im Ranking (1 = Beste). Example: 5
     * @responseField ranking.total_families integer Gesamtanzahl der Familien. Example: 50
     * @responseField ranking.avg_progress float Durchschnittlicher Fortschritt aller Familien. Example: 65.3
     * @responseField ranking.better_than_average boolean Ob über dem Durchschnitt. Example: true
     * @responseField payment object Zahlungsinformationen
     * @responseField payment.remaining_payment float Noch zu zahlender Betrag. Example: 73.5
     * @responseField payment.currency string Währung. Example: €
     *
     * @response 200 scenario="Erfolgreich" {
     *   "progress": {
     *     "percent": 75.5,
     *     "total_minutes_completed": 906,
     *     "total_hours_completed": 15.1,
     *     "required_minutes": 1200,
     *     "required_hours": 20,
     *     "open_minutes": 294,
     *     "open_hours": 4.9,
     *     "is_completed": false
     *   },
     *   "ranking": {
     *     "your_rank": 5,
     *     "total_families": 50,
     *     "avg_progress": 65.3,
     *     "better_than_average": true
     *   },
     *   "payment": {
     *     "remaining_payment": 73.5,
     *     "currency": "€"
     *   }
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "message": "Berechtigung fehlt"
     * }
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        if (!$user->can('view Pflichtstunden')) {
            return response()->json(['message' => 'Berechtigung fehlt'], 403);
        }

        $stats = $this->calculateParentStats($user);

        return response()->json(new PflichtstundeStatsResource($stats));
    }

    /**
     * Neue Pflichtstunde anlegen
     *
     * Erstellt eine neue Pflichtstunde für den angemeldeten User.
     * Die Pflichtstunde wird zunächst im Status "pending" angelegt und muss
     * von einem Administrator genehmigt werden.
     *
     * @authenticated
     *
     * @bodyParam start datetime required Start-Zeitpunkt der Pflichtstunde (YYYY-MM-DD HH:MM:SS). Example: 2024-03-15 09:00:00
     * @bodyParam end datetime required End-Zeitpunkt der Pflichtstunde (YYYY-MM-DD HH:MM:SS). Example: 2024-03-15 12:00:00
     * @bodyParam description string required Beschreibung der Tätigkeit (max. 500 Zeichen). Example: Schulhof säubern
     * @bodyParam bereich string Bereich der Pflichtstunde (optional). Example: Schulhof
     *
     * @responseField message string Erfolgsmeldung
     * @responseField data object Die neu erstellte Pflichtstunde
     *
     * @response 201 scenario="Erfolgreich erstellt" {
     *   "message": "Pflichtstunde erfolgreich angelegt",
     *   "data": {
     *     "id": 42,
     *     "user_id": 42,
     *     "user_name": "Max Mustermann",
     *     "start": "2024-03-15T09:00:00.000000Z",
     *     "end": "2024-03-15T12:00:00.000000Z",
     *     "duration_minutes": 180,
     *     "duration_hours": 3,
     *     "description": "Schulhof säubern",
     *     "bereich": "Schulhof",
     *     "status": "upcoming",
     *     "approved": false,
     *     "approved_at": null,
     *     "approved_by": null,
     *     "approver_name": null,
     *     "rejected": false,
     *     "rejected_at": null,
     *     "rejected_by": null,
     *     "rejector_name": null,
     *     "rejection_reason": null,
     *     "listen_termin_id": null,
     *     "created_at": "2024-03-15T10:30:00.000000Z",
     *     "updated_at": "2024-03-15T10:30:00.000000Z"
     *   }
     * }
     *
     * @response 422 scenario="Validierungsfehler" {
     *   "message": "The given data was invalid.",
     *   "errors": {
     *     "start": ["The start field is required."],
     *     "end": ["The end field is required."],
     *     "description": ["The description field is required."]
     *   }
     * }
     *
     * @param CreatePflichtstundeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreatePflichtstundeRequest $request)
    {
        $data = $request->validated();

        // Setze user_id auf den angemeldeten User
        // API-User können nur für sich selbst Pflichtstunden anlegen
        $data['user_id'] = $request->user()->id;

        $pflichtstunde = Pflichtstunde::create($data);

        return response()->json([
            'message' => 'Pflichtstunde erfolgreich angelegt',
            'data' => new PflichtstundeResource($pflichtstunde),
        ], 201);
    }

    /**
     * Pflichtstunde bearbeiten
     *
     * Aktualisiert eine bestehende Pflichtstunde. Nur möglich, wenn:
     * - Die Pflichtstunde dem angemeldeten User gehört
     * - Die Pflichtstunde noch nicht bestätigt wurde
     * - Die Pflichtstunde noch nicht abgelehnt wurde
     *
     * @authenticated
     *
     * @urlParam pflichtstunde required Die ID der Pflichtstunde. Example: 42
     *
     * @bodyParam start datetime Start-Zeitpunkt der Pflichtstunde (YYYY-MM-DD HH:MM:SS). Example: 2024-03-15 10:00:00
     * @bodyParam end datetime End-Zeitpunkt der Pflichtstunde (YYYY-MM-DD HH:MM:SS). Example: 2024-03-15 13:00:00
     * @bodyParam description string Beschreibung der Tätigkeit (max. 500 Zeichen). Example: Schulhof säubern (aktualisiert)
     * @bodyParam bereich string Bereich der Pflichtstunde. Example: Schulhof
     *
     * @responseField message string Erfolgsmeldung
     * @responseField data object Die aktualisierte Pflichtstunde
     *
     * @response 200 scenario="Erfolgreich aktualisiert" {
     *   "message": "Pflichtstunde erfolgreich aktualisiert",
     *   "data": {
     *     "id": 42,
     *     "user_id": 42,
     *     "user_name": "Max Mustermann",
     *     "start": "2024-03-15T10:00:00.000000Z",
     *     "end": "2024-03-15T13:00:00.000000Z",
     *     "duration_minutes": 180,
     *     "duration_hours": 3,
     *     "description": "Schulhof säubern (aktualisiert)",
     *     "bereich": "Schulhof",
     *     "status": "upcoming",
     *     "approved": false,
     *     "approved_at": null,
     *     "approved_by": null,
     *     "approver_name": null,
     *     "rejected": false,
     *     "rejected_at": null,
     *     "rejected_by": null,
     *     "rejector_name": null,
     *     "rejection_reason": null,
     *     "listen_termin_id": null,
     *     "created_at": "2024-03-15T10:30:00.000000Z",
     *     "updated_at": "2024-03-15T11:00:00.000000Z"
     *   }
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "message": "Berechtigung fehlt"
     * }
     *
     * @response 403 scenario="Bereits bestätigt/abgelehnt" {
     *   "message": "Pflichtstunde kann nicht mehr bearbeitet werden, da sie bereits bestätigt oder abgelehnt wurde."
     * }
     *
     * @response 404 scenario="Nicht gefunden" {
     *   "message": "No query results for model [App\\Model\\Pflichtstunde]."
     * }
     *
     * @param UpdatePflichtstundeRequest $request
     * @param Pflichtstunde $pflichtstunde
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdatePflichtstundeRequest $request, Pflichtstunde $pflichtstunde)
    {
        $user = $request->user();

        // Prüfe ob User Eigentümer ist
        if ($pflichtstunde->user_id !== $user->id) {
            return response()->json(['message' => 'Berechtigung fehlt'], 403);
        }

        // Prüfe ob bereits bestätigt oder abgelehnt
        if ($pflichtstunde->approved || $pflichtstunde->rejected) {
            return response()->json([
                'message' => 'Pflichtstunde kann nicht mehr bearbeitet werden, da sie bereits bestätigt oder abgelehnt wurde.',
            ], 403);
        }

        $data = $request->validated();
        // user_id darf nicht geändert werden
        unset($data['user_id']);

        $pflichtstunde->update($data);

        return response()->json([
            'message' => 'Pflichtstunde erfolgreich aktualisiert',
            'data' => new PflichtstundeResource($pflichtstunde->fresh()),
        ]);
    }

    /**
     * Pflichtstunde löschen
     *
     * Löscht eine Pflichtstunde. Nur möglich, wenn:
     * - Die Pflichtstunde dem angemeldeten User gehört
     * - Die Pflichtstunde noch nicht bestätigt wurde
     * - Die Pflichtstunde noch nicht abgelehnt wurde
     *
     * @authenticated
     *
     * @urlParam pflichtstunde required Die ID der Pflichtstunde. Example: 42
     *
     * @responseField message string Erfolgsmeldung
     *
     * @response 200 scenario="Erfolgreich gelöscht" {
     *   "message": "Pflichtstunde erfolgreich gelöscht"
     * }
     *
     * @response 403 scenario="Keine Berechtigung" {
     *   "message": "Berechtigung fehlt"
     * }
     *
     * @response 403 scenario="Bereits bestätigt/abgelehnt" {
     *   "message": "Pflichtstunde kann nicht mehr gelöscht werden, da sie bereits bestätigt oder abgelehnt wurde."
     * }
     *
     * @response 404 scenario="Nicht gefunden" {
     *   "message": "No query results for model [App\\Model\\Pflichtstunde]."
     * }
     *
     * @param Request $request
     * @param Pflichtstunde $pflichtstunde
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Pflichtstunde $pflichtstunde)
    {
        $user = $request->user();

        // Prüfe ob User Eigentümer ist
        if ($pflichtstunde->user_id !== $user->id) {
            return response()->json(['message' => 'Berechtigung fehlt'], 403);
        }

        // Prüfe ob bereits bestätigt oder abgelehnt
        if ($pflichtstunde->approved || $pflichtstunde->rejected) {
            return response()->json([
                'message' => 'Pflichtstunde kann nicht mehr gelöscht werden, da sie bereits bestätigt oder abgelehnt wurde.',
            ], 403);
        }

        $pflichtstunde->delete();

        return response()->json([
            'message' => 'Pflichtstunde erfolgreich gelöscht',
        ]);
    }

    /**
     * Berechne Statistiken für Ranking und Vergleich
     */
    private function calculateParentStats(\App\Model\User $currentUser)
    {
        [$periodStart, $periodEnd] = $this->familyService->resolvePeriod(null);
        $summaries = $this->familyService->buildFamilySummaries($periodStart, $periodEnd, true);
        $sorted = $summaries->sortByDesc('percent')->values();
        $current = $summaries->first(fn (array $summary) => in_array($currentUser->id, $summary['user_ids']));

        $yourRank = 1;
        foreach ($sorted as $index => $summary) {
            if (in_array($currentUser->id, $summary['user_ids'])) {
                $yourRank = $index + 1;
                break;
            }
        }

        $currentRequiredMinutes = (int) ($current['required_minutes'] ?? ((int) $this->pflichtstunden_settings->pflichtstunden_anzahl * 60));
        $openMinutes = (int) ($current['openMinutes'] ?? 0);

        return [
            'total_parents' => $summaries->count(),
            'your_rank' => $yourRank,
            'avg_progress' => round((float) $summaries->avg('percent'), 2),
            'your_progress' => (float) ($current['percent'] ?? 0),
            'total_minutes_completed' => (int) ($current['totalMinutes'] ?? 0),
            'total_hours_completed' => round(((int) ($current['totalMinutes'] ?? 0)) / 60, 2),
            'required_minutes' => $currentRequiredMinutes,
            'required_hours' => round($currentRequiredMinutes / 60, 2),
            'open_minutes' => $openMinutes,
            'open_hours' => round($openMinutes / 60, 2),
            'remaining_payment' => (float) ($current['beitrag'] ?? 0),
            'rule_mode' => $current['rule_mode'] ?? 'standard',
            'opening_balance_minutes' => (int) ($current['opening_balance_minutes'] ?? 0),
            'closing_balance_minutes' => (int) ($current['closing_balance_minutes'] ?? 0),
            'carryover_preview_minutes' => (int) ($current['carryover_preview_minutes'] ?? 0),
        ];
    }
}
