<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\V1\ChildResource;
use App\Mail\NeuerTeilnehmerMail;
use App\Model\Arbeitsgemeinschaft;
use App\Model\Child;
use App\Model\Group;
use App\Model\Reinigung;
use App\Model\ReinigungsTask;
use App\Services\App\Family;
use App\Services\App\KrankmeldungService;
use App\Services\FamilyWeeklyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * @group App: Familie
 */
class ParentController extends ApiController
{
    /**
     * Kind krankmelden – B-40 (nur eigene Kinder, Datum `Y-m-d`, idempotent).
     *
     * @bodyParam child_id int required
     * @bodyParam start date required Example: 2026-09-25
     * @bodyParam ende date required Example: 2026-09-26
     * @bodyParam kommentar string
     * @bodyParam disease_id int Meldepflichtige Erkrankung.
     */
    public function storeKrankmeldung(Request $request, KrankmeldungService $service): JsonResponse
    {
        $data = $request->validate([
            'child_id' => 'required|integer|exists:children,id',
            'start' => 'required|date_format:Y-m-d|after_or_equal:'.today()->subDays(14)->toDateString(),
            'ende' => 'required|date_format:Y-m-d|after_or_equal:start',
            'kommentar' => 'nullable|string|max:2000',
            'disease_id' => 'nullable|integer|exists:diseases,id',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|max:10240',
        ]);
        $user = $request->user();
        abort_unless(Family::ownsChild($user, (int) $data['child_id']), 403, 'Sie können nur Ihre eigenen Kinder krankmelden.');

        $krankmeldung = $service->create(
            $user,
            Child::findOrFail($data['child_id']),
            null,
            Carbon::createFromFormat('Y-m-d', $data['start']),
            Carbon::createFromFormat('Y-m-d', $data['ende']),
            trim((string) ($data['kommentar'] ?? '')) ?: 'Krank',
            $data['disease_id'] ?? null,
            $request->file('files', []),
        );

        return response()->json(['data' => [
            'id' => $krankmeldung->id,
            'child_id' => $krankmeldung->child_id,
            'start' => $krankmeldung->start->toDateString(),
            'ende' => $krankmeldung->ende->toDateString(),
            'kommentar' => $krankmeldung->kommentar,
            'readmission_hint' => $service->readmissionHint($krankmeldung->disease_id),
        ], 'message' => 'Krankmeldung gesendet.'], 201);
    }

    /**
     * Einstellungen je Kind ändern – B-42 (Benachrichtigung bei Check-in/-out).
     *
     * @bodyParam notification boolean required
     */
    public function updateChild(Request $request, Child $child): JsonResponse
    {
        abort_unless(Family::ownsChild($request->user(), $child), 403);
        $request->validate(['notification' => 'required|boolean']);
        $child->update(['notification' => $request->boolean('notification')]);

        return response()->json(['data' => new ChildResource($child->fresh(['group', 'class']))]);
    }

    /**
     * Stundenplan eines Kindes für eine Woche – B-43.
     *
     * @queryParam week string ISO-Woche `YYYY-Www`. Example: 2026-W40
     */
    public function stundenplan(Request $request, Child $child, FamilyWeeklyService $weekly): JsonResponse
    {
        $user = $request->user();
        abort_unless(Family::ownsChild($user, $child), 403);
        $request->validate(['week' => ['nullable', 'regex:/^\d{4}-W\d{1,2}$/']]);

        $weekStart = null;
        if ($week = $request->input('week')) {
            [$y, $w] = explode('-W', $week);
            $weekStart = now()->setISODate((int) $y, (int) $w)->startOfWeek();
        }
        $data = $weekly->getWeeklyData($user, $weekStart);
        $entry = $data['children']->first(fn ($c) => $c['child']->id === $child->id);

        return response()->json(['data' => [
            'klasse' => $entry['klasse'] ?? null,
            'week_start' => $data['week_start']->toDateString(),
            'days' => collect($entry['days'] ?? [])->map(fn ($d, $day) => [
                'day' => $day,
                'date' => $d['date']->toDateString(),
                'is_holiday' => (bool) $d['is_holiday'],
                'holiday_name' => $d['holiday_name'],
                'lessons' => array_values($d['stundenplan'] ?? []),
                'vertretungen' => $d['vertretungen']->values(),
            ])->values(),
        ]]);
    }

    /**
     * Arbeitsgemeinschaften, für die die eigenen Kinder in Frage kommen – B-45.
     */
    public function ags(Request $request): JsonResponse
    {
        $children = Family::children($request->user());
        $groupIds = $children->pluck('group_id')->merge($children->pluck('class_id'))->filter()->unique();

        $ags = Arbeitsgemeinschaft::query()
            ->with(['manager:id,name', 'groups:id', 'participants:id'])
            ->where('end_date', '>', now())
            ->whereHas('groups', fn ($q) => $q->withoutGlobalScopes()->whereIn('groups.id', $groupIds))
            ->orderBy('weekday')->orderBy('start_time')
            ->get();

        return response()->json(['data' => $ags->map(function (Arbeitsgemeinschaft $ag) use ($children) {
            $agGroups = $ag->groups->pluck('id');
            $eligible = $children->filter(fn ($c) => $agGroups->contains($c->group_id) || $agGroups->contains($c->class_id));

            return [
                'id' => $ag->id,
                'name' => $ag->name,
                'description' => $ag->description,
                'weekday' => (int) $ag->weekday,
                'start_time' => $ag->start_time?->format('H:i'),
                'end_time' => $ag->end_time?->format('H:i'),
                'start_date' => $ag->start_date?->toDateString(),
                'end_date' => $ag->end_date?->toDateString(),
                'manager' => $ag->manager?->name,
                'max_participants' => (int) $ag->max_participants,
                'participants_count' => $ag->participants->count(),
                'eligible_children' => $eligible->pluck('id')->values(),
                'enrolled_children' => $eligible->pluck('id')->intersect($ag->participants->pluck('id'))->values(),
            ];
        })->values()]);
    }

    /**
     * Kind für eine AG anmelden (gegen Überbuchung gesperrt).
     *
     * @bodyParam child_id int required
     */
    public function enroll(Request $request, Arbeitsgemeinschaft $ag): JsonResponse
    {
        $request->validate(['child_id' => 'required|integer']);
        $user = $request->user();
        abort_unless(Family::ownsChild($user, (int) $request->child_id), 403, 'Das Kind gehört nicht zu Ihrer Familie.');
        $child = Child::findOrFail($request->child_id);
        $agGroups = $ag->groups()->withoutGlobalScopes()->pluck('groups.id');
        abort_unless($agGroups->contains($child->group_id) || $agGroups->contains($child->class_id), 422, 'Das Kind gehört nicht zu einer der erlaubten Gruppen.');
        abort_if($ag->end_date && $ag->end_date->isPast(), 410, 'Die AG ist bereits beendet.');

        $result = DB::transaction(function () use ($ag, $child, $user) {
            $count = DB::table('arbeitsgemeinschaften_participants')->where('ag_id', $ag->id)->lockForUpdate()->count();
            if (DB::table('arbeitsgemeinschaften_participants')->where('ag_id', $ag->id)->where('participant_id', $child->id)->exists()) {
                return 'exists';
            }
            if ($ag->max_participants && $count >= $ag->max_participants) {
                return 'full';
            }
            $ag->participants()->attach($child->id, ['user_id' => $user->id]);

            return 'ok';
        });

        if ($result === 'exists') {
            return response()->json(['message' => 'Das Kind ist bereits angemeldet.'], 409);
        }
        if ($result === 'full') {
            return response()->json(['message' => 'Die maximale Teilnehmerzahl ist bereits erreicht.'], 409);
        }

        // Wie im Web: Eltern in die AG-Gruppe aufnehmen, AG-Leitung informieren.
        if ($agGroup = Group::withoutGlobalScopes()->where('name', $ag->name)->first()) {
            $agGroup->users()->syncWithoutDetaching($child->parents->pluck('id')->merge(Family::userIds($user))->unique()->all());
        }
        try {
            if ($ag->manager?->email) {
                Mail::to($ag->manager->email)->queue(new NeuerTeilnehmerMail($ag, $child));
            }
        } catch (\Throwable $e) {
            Log::error('AG-Anmeldung (App): Mail an AG-Leitung fehlgeschlagen: '.$e->getMessage());
        }

        return response()->json(['message' => 'Das Kind wurde angemeldet.'], 201);
    }

    /**
     * Reinigungsplan der Familie – B-51.
     */
    public function reinigung(Request $request): JsonResponse
    {
        $family = Family::userIds($request->user());
        $termine = Reinigung::query()
            ->whereIn('users_id', $family)
            ->whereDate('datum', '>=', today()->startOfWeek())
            ->orderBy('datum')
            ->get(['bereich', 'aufgabe', 'datum', 'bemerkung']);

        return response()->json(['data' => [
            'eigene' => $termine->map(fn ($r) => [
                'datum' => Carbon::parse($r->getRawOriginal('datum'))->toDateString(),
                'woche_bis' => Carbon::parse($r->getRawOriginal('datum'))->endOfWeek()->toDateString(),
                'bereich' => $r->bereich,
                'aufgabe' => $r->aufgabe,
                'bemerkung' => $r->bemerkung,
            ])->values(),
            'aufgaben' => ReinigungsTask::query()->pluck('task'),
        ]]);
    }
}
