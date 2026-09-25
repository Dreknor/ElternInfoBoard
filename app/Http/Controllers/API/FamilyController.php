<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChildRelationResource;
use App\Http\Resources\FamilyResource;
use App\Http\Resources\GuardianResource;
use App\Model\Child;
use App\Model\User;
use App\Services\Family\FamilyResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/**
 * Familie und Kind-Beziehungen für die App (kind-zentriertes Familienmodell).
 *
 * @group Familie
 */
class FamilyController extends Controller implements HasMiddleware
{
    public function __construct(private readonly FamilyResolver $resolver) {}

    public static function middleware(): array
    {
        return ['auth:sanctum'];
    }

    /**
     * Eigene Familie
     *
     * Gibt die Familie des angemeldeten Users mit Mitgliedern und Kindern zurück.
     * Ohne gepflegte Familie werden die Mitglieder aus der bisherigen Kontoverknüpfung geliefert.
     *
     * @authenticated
     *
     * @responseField data.id integer|null ID der Familie (null, wenn keine Familie gepflegt ist)
     * @responseField data.name string Name der Familie
     * @responseField data.members array Mitglieder (id, name)
     * @responseField data.children array Kinder der Mitglieder (id, first_name, last_name)
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->family) {
            return response()->json(['success' => true, 'data' => new FamilyResource($user->family->load('users'))]);
        }

        $members = User::query()->whereIn('id', $this->resolver->familyUserIds($user))->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => null,
                'name' => $this->resolver->familyLabel($user),
                'members' => $members->map(fn (User $m) => ['id' => $m->id, 'name' => $m->name])->values(),
                'children' => $this->resolver->childrenFor($user)->map(fn (Child $c) => [
                    'id' => $c->id, 'first_name' => $c->first_name, 'last_name' => $c->last_name,
                ])->values(),
            ],
        ]);
    }

    /**
     * Eigene Beziehungen
     *
     * Kinder, mit denen der angemeldete User direkt verknüpft ist – inkl. Beziehungsart,
     * Rechten, Herkunft und ob die Verbindung noch geprüft werden muss.
     *
     * @authenticated
     *
     * @responseField family object|null Familie (id, name)
     * @responseField children array Kinder mit relation, rights, source, pending_review
     */
    public function relations(Request $request): JsonResponse
    {
        $user = $request->user()->load(['family', 'children_rel.class', 'children_rel.group']);

        return response()->json([
            'success' => true,
            'family' => $user->family ? ['id' => $user->family->id, 'name' => $user->family->name] : null,
            'children' => ChildRelationResource::collection($user->children_rel),
        ]);
    }

    /**
     * Bezugspersonen eines Kindes
     *
     * Für Bezugspersonen des Kindes und Care-Personal.
     *
     * @authenticated
     *
     * @urlParam child integer required ID des Kindes. Example: 1
     *
     * @responseField data array Bezugspersonen (id, name, relation, has_custody, receives_information, can_manage)
     */
    public function guardians(Request $request, Child $child): JsonResponse
    {
        if ($request->user()->cannot('view', $child)) {
            return response()->json(['success' => false, 'message' => 'Keine Berechtigung'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => GuardianResource::collection($child->parents()->orderBy('name')->get()),
        ]);
    }
}
