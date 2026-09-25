<?php

namespace App\Http\Controllers\API\V1;

use App\Model\Liste;
use App\Model\Listen_Eintragungen;
use App\Model\listen_termine;
use App\Services\App\Family;
use App\Services\App\ListenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group App: Listen
 */
class ListenController extends ApiController
{
    public function __construct(private readonly ListenService $service) {}

    /**
     * Offene Listen mit eigenen Buchungen und freien Plätzen – B-32.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $family = Family::userIds($user);

        $listen = Liste::query()
            ->where('active', true)
            ->whereDate('ende', '>=', today())
            ->whereExists(function ($q) use ($user) {
                $q->selectRaw(1)->from('group_listen')
                    ->join('group_user', 'group_listen.group_id', '=', 'group_user.group_id')
                    ->whereColumn('group_listen.liste_id', 'listen.id')
                    ->where('group_user.user_id', $user->id);
            })
            ->orderBy('ende')
            ->get();

        $ids = $listen->pluck('id');
        $termine = DB::table('listen_termine')->whereIn('listen_id', $ids)->where('termin', '>=', now())
            ->selectRaw('listen_id, sum(case when reserviert_fuer is null then 1 else 0 end) as free')
            ->selectRaw('sum(case when reserviert_fuer in ('.implode(',', array_map('intval', $family)).') then 1 else 0 end) as mine')
            ->groupBy('listen_id')->get()->keyBy('listen_id');
        $eintraege = DB::table('listen_eintragungen')->whereIn('listen_id', $ids)
            ->selectRaw('listen_id, sum(case when user_id is null then 1 else 0 end) as free')
            ->selectRaw('sum(case when user_id in ('.implode(',', array_map('intval', $family)).') then 1 else 0 end) as mine')
            ->groupBy('listen_id')->get()->keyBy('listen_id');

        return response()->json(['data' => $listen->map(function (Liste $l) use ($termine, $eintraege) {
            $stats = $l->type === 'termin' ? $termine->get($l->id) : $eintraege->get($l->id);

            return $this->presentListe($l) + [
                'my_bookings_count' => (int) ($stats->mine ?? 0),
                'free_count' => (int) ($stats->free ?? 0),
            ];
        })->values()]);
    }

    /**
     * Liste mit Terminen bzw. Einträgen. `mine` = Buchung der Familie; Namen anderer nur bei „für alle sichtbar“.
     */
    public function show(Request $request, Liste $liste): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->service->canAccess($user, $liste), 403, 'Sie haben keinen Zugriff auf diese Liste.');
        $family = Family::userIds($user);
        $showNames = $liste->visible_for_all || $user->can('edit terminliste') || (int) $liste->besitzer === $user->id;

        $data = $this->presentListe($liste);
        if ($liste->type === 'termin') {
            $data['termine'] = listen_termine::query()
                ->where('listen_id', $liste->id)
                ->where('termin', '>=', now()->startOfDay())
                ->with('eingetragenePerson:id,name')
                ->orderBy('termin')
                ->get()
                ->map(fn (listen_termine $t) => [
                    'id' => $t->id,
                    'start' => $t->termin->toIso8601String(),
                    'duration' => (int) ($t->duration ?: $liste->duration ?: 0),
                    'comment' => $t->comment,
                    'status' => $t->reserviert_fuer === null ? 'free' : (in_array((int) $t->reserviert_fuer, $family, true) ? 'mine' : 'taken'),
                    'booked_by' => $showNames && $t->reserviert_fuer ? $t->eingetragenePerson?->name : null,
                ])->values();
        } else {
            $data['eintraege'] = Listen_Eintragungen::query()
                ->where('listen_id', $liste->id)
                ->with('user:id,name')
                ->orderBy('id')
                ->get()
                ->map(fn (Listen_Eintragungen $e) => [
                    'id' => $e->id,
                    'text' => $e->eintragung,
                    'status' => $e->user_id === null ? 'free' : (in_array((int) $e->user_id, $family, true) ? 'mine' : 'taken'),
                    'booked_by' => $showNames && $e->user_id ? $e->user?->name : null,
                    'own_entry' => in_array((int) $e->created_by, $family, true),
                ])->values();
        }
        $data['my_bookings_count'] = $liste->type === 'termin'
            ? $this->service->familyTerminCount($user, $liste)
            : $this->service->familyEintragCount($user, $liste);

        return response()->json(['data' => $data]);
    }

    /** Termin buchen (atomar, 409 bei Konflikt) – B-31. */
    public function reserveTermin(Request $request, listen_termine $termin): JsonResponse
    {
        $this->service->reserveTermin($request->user(), $termin);

        return response()->json(['message' => 'Termin gebucht.'], 201);
    }

    /**
     * Termin absagen (Familie, Besitzer); informiert Ersteller und gebuchte Person.
     *
     * @bodyParam reason string Grund (optional).
     */
    public function cancelTermin(Request $request, listen_termine $termin): JsonResponse
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $this->service->cancelTermin($request->user(), $termin, $request->input('reason'));

        return response()->json(['message' => 'Termin abgesagt.']);
    }

    /** Eigenen Eintrag hinzufügen. */
    public function addEintrag(Request $request, Liste $liste): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:500']);
        $eintrag = $this->service->addEintrag($request->user(), $liste, $request->text);

        return response()->json(['data' => ['id' => $eintrag->id], 'message' => 'Eingetragen.'], 201);
    }

    public function reserveEintrag(Request $request, Listen_Eintragungen $eintrag): JsonResponse
    {
        $this->service->reserveEintrag($request->user(), $eintrag);

        return response()->json(['message' => 'Eingetragen.'], 201);
    }

    public function cancelEintrag(Request $request, Listen_Eintragungen $eintrag): JsonResponse
    {
        $this->service->cancelEintrag($request->user(), $eintrag);

        return response()->json(['message' => 'Ausgetragen.']);
    }

    private function presentListe(Liste $l): array
    {
        return [
            'id' => $l->id,
            'name' => $l->listenname,
            'comment' => $l->comment,
            'type' => $l->type,
            'multiple' => (bool) $l->multiple,
            'ende' => $l->ende?->toIso8601String(),
            'duration' => (int) $l->duration,
            'visible_for_all' => (bool) $l->visible_for_all,
        ];
    }
}
