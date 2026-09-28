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
        $myChildIds = Family::childIds($user);
        $showNames = $liste->visible_for_all || $user->can('edit terminliste') || (int) $liste->besitzer === $user->id;
        // Buchung gehört der Familie oder (bei Listen je Kind) einem eigenen Kind
        $isMine = fn (?int $bookedBy, ?int $childId) => ($bookedBy !== null && in_array($bookedBy, $family, true))
            || ($childId !== null && in_array($childId, $myChildIds, true));
        $childInfo = fn ($child, bool $visible) => $child && $visible
            ? ['id' => $child->id, 'name' => trim($child->first_name.' '.$child->last_name)]
            : null;

        $data = $this->presentListe($liste);
        $data['bookable_children'] = $this->service->bookableChildren($user, $liste)
            ->map(fn ($c) => ['id' => $c->id, 'name' => trim($c->first_name.' '.$c->last_name)])->values();
        if ($liste->type === 'termin') {
            $data['termine'] = listen_termine::query()
                ->where('listen_id', $liste->id)
                ->where('termin', '>=', now()->startOfDay())
                ->with(['eingetragenePerson:id,name', 'child:id,first_name,last_name'])
                ->orderBy('termin')
                ->get()
                ->map(fn (listen_termine $t) => [
                    'id' => $t->id,
                    'start' => $t->termin->toIso8601String(),
                    'duration' => (int) ($t->duration ?: $liste->duration ?: 0),
                    'comment' => $t->comment,
                    'status' => $t->reserviert_fuer === null ? 'free' : ($isMine((int) $t->reserviert_fuer, $t->child_id) ? 'mine' : 'taken'),
                    'booked_by' => ($showNames || $isMine((int) $t->reserviert_fuer, $t->child_id)) && $t->reserviert_fuer ? $t->eingetragenePerson?->name : null,
                    'child' => $childInfo($t->child, $showNames || $isMine((int) $t->reserviert_fuer, $t->child_id)),
                ])->values();
        } else {
            $data['eintraege'] = Listen_Eintragungen::query()
                ->where('listen_id', $liste->id)
                ->with(['user:id,name', 'child:id,first_name,last_name'])
                ->orderBy('id')
                ->get()
                ->map(fn (Listen_Eintragungen $e) => [
                    'id' => $e->id,
                    'text' => $e->eintragung,
                    'status' => $e->user_id === null ? 'free' : ($isMine((int) $e->user_id, $e->child_id) ? 'mine' : 'taken'),
                    'booked_by' => ($showNames || $isMine((int) $e->user_id, $e->child_id)) && $e->user_id ? $e->user?->name : null,
                    'child' => $childInfo($e->child, $showNames || $isMine((int) $e->user_id, $e->child_id)),
                    'own_entry' => in_array((int) $e->created_by, $family, true),
                ])->values();
        }
        $data['my_bookings_count'] = $liste->type === 'termin'
            ? $this->service->familyTerminCount($user, $liste)
            : $this->service->familyEintragCount($user, $liste);

        return response()->json(['data' => $data]);
    }

    /**
     * Termin buchen (atomar, 409 bei Konflikt) – B-31.
     *
     * @bodyParam child_id integer Kind der Buchung; Pflicht bei `booking_scope = child` und mehreren Kindern.
     */
    public function reserveTermin(Request $request, listen_termine $termin): JsonResponse
    {
        $request->validate(['child_id' => 'nullable|integer']);
        $this->service->reserveTermin($request->user(), $termin, $request->integer('child_id') ?: null);

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

    /**
     * Eigenen Eintrag hinzufügen.
     *
     * @bodyParam child_id integer Kind des Eintrags; Pflicht bei `booking_scope = child` und mehreren Kindern.
     */
    public function addEintrag(Request $request, Liste $liste): JsonResponse
    {
        $request->validate(['text' => 'required|string|max:500', 'child_id' => 'nullable|integer']);
        $eintrag = $this->service->addEintrag($request->user(), $liste, $request->text, $request->integer('child_id') ?: null);

        return response()->json(['data' => ['id' => $eintrag->id], 'message' => 'Eingetragen.'], 201);
    }

    public function reserveEintrag(Request $request, Listen_Eintragungen $eintrag): JsonResponse
    {
        $request->validate(['child_id' => 'nullable|integer']);
        $this->service->reserveEintrag($request->user(), $eintrag, $request->integer('child_id') ?: null);

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
            // "family" (bisher) oder "child": dann child_id beim Buchen mitsenden
            'booking_scope' => $l->booking_scope ?? Liste::BOOKING_FAMILY,
        ];
    }
}
