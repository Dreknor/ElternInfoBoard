<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Class CareController
 *
 * Controller for handling Care related API requests.
 */
class CareController extends Controller
{
    /**
     * CareController constructor.
     *
     * Apply authentication middleware.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get all currently present children in the Care area.
     *
     * Returns a list of all children who are currently checked in and not checked out today.
     *
     * @group Care
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of currently present children.
     * @responseField data.*.id int The ID of the child.
     * @responseField data.*.first_name string The first name of the child.
     * @responseField data.*.last_name string The last name of the child.
     * @responseField data.*.group_id int The group ID of the child.
     * @responseField data.*.group object The group information.
     * @responseField data.*.class_id int The class ID of the child.
     * @responseField data.*.class object The class information.
     * @responseField data.*.checked_in_at datetime The time the child was checked in.
     * @responseField data.*.is_sick boolean Whether the child is currently reported sick.
     * @responseField count int The total count of present children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPresentChildren(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has permission to view care data
        if (!$user->can('edit schickzeiten')) {
            return response()->json([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ], 403);
        }

        $careSettings = new CareSetting();

        // Get all children who are currently checked in
        $children = Child::query()
            ->whereIn('group_id', $careSettings->groups_list)
            ->whereIn('class_id', $careSettings->class_list)
            ->whereHas('checkIns', function ($query) {
                $query
                    ->where('checked_in', true)
                    ->where('checked_out', false)
                    ->whereDate('date', now()->toDateString());
            })
            ->with([
                'group:id,name',
                'class:id,name',
                'checkIns' => function ($query) {
                    $query
                        ->where('checked_in', true)
                        ->where('checked_out', false)
                        ->whereDate('date', now()->toDateString())
                        ->select('id', 'child_id', 'checked_in', 'checked_out', 'date', 'created_at');
                }
            ])
            ->get()
            ->map(function ($child) {
                $checkIn = $child->checkIns->first();
                return [
                    'id' => $child->id,
                    'first_name' => $child->first_name,
                    'last_name' => $child->last_name,
                    'group_id' => $child->group_id,
                    'group' => $child->group ? [
                        'id' => $child->group->id,
                        'name' => $child->group->name,
                    ] : null,
                    'class_id' => $child->class_id,
                    'class' => $child->class ? [
                        'id' => $child->class->id,
                        'name' => $child->class->name,
                    ] : null,
                    'checked_in_at' => $checkIn ? $checkIn->created_at->toIso8601String() : null,
                    'is_sick' => $child->krankmeldungToday(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $children->values(),
            'count' => $children->count(),
        ], 200);
    }

    /**
     * Get all children who are currently reported sick in the Care area.
     *
     * Returns a list of all children who have an active sick report (Krankmeldung) today.
     *
     * @group Care
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of children currently reported sick.
     * @responseField data.*.id int The ID of the child.
     * @responseField data.*.first_name string The first name of the child.
     * @responseField data.*.last_name string The last name of the child.
     * @responseField data.*.group_id int The group ID of the child.
     * @responseField data.*.group object The group information.
     * @responseField data.*.class_id int The class ID of the child.
     * @responseField data.*.class object The class information.
     * @responseField data.*.krankmeldung object The sick report information.
     * @responseField data.*.krankmeldung.id int The ID of the sick report.
     * @responseField data.*.krankmeldung.name string The name/reason of the sick report.
     * @responseField data.*.krankmeldung.kommentar string The comment of the sick report.
     * @responseField data.*.krankmeldung.start string The start date of the sick report.
     * @responseField data.*.krankmeldung.ende string The end date of the sick report.
     * @responseField data.*.krankmeldung.disease object The disease information (if available).
     * @responseField count int The total count of sick children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSickChildren(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has permission to view care data
        if (!$user->can('edit schickzeiten')) {
            return response()->json([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ], 403);
        }

        $careSettings = new CareSetting();

        // Get all children in care area who have active sick reports today
        $children = Child::query()
            ->whereIn('group_id', $careSettings->groups_list)
            ->whereIn('class_id', $careSettings->class_list)
            ->whereHas('krankmeldungen', function ($query) {
                $query->whereDate('start', '<=', today())
                    ->whereDate('ende', '>=', today());
            })
            ->with([
                'group:id,name',
                'class:id,name',
                'krankmeldungen' => function ($query) {
                    $query->whereDate('start', '<=', today())
                        ->whereDate('ende', '>=', today())
                        ->with('disease:id,name')
                        ->orderByDesc('created_at');
                }
            ])
            ->get()
            ->map(function ($child) {
                $krankmeldung = $child->krankmeldungen->first();
                return [
                    'id' => $child->id,
                    'first_name' => $child->first_name,
                    'last_name' => $child->last_name,
                    'group_id' => $child->group_id,
                    'group' => $child->group ? [
                        'id' => $child->group->id,
                        'name' => $child->group->name,
                    ] : null,
                    'class_id' => $child->class_id,
                    'class' => $child->class ? [
                        'id' => $child->class->id,
                        'name' => $child->class->name,
                    ] : null,
                    'krankmeldung' => $krankmeldung ? [
                        'id' => $krankmeldung->id,
                        'name' => $krankmeldung->name,
                        'kommentar' => $krankmeldung->kommentar,
                        'start' => $krankmeldung->start->toDateString(),
                        'ende' => $krankmeldung->ende->toDateString(),
                        'disease' => $krankmeldung->disease ? [
                            'id' => $krankmeldung->disease->id,
                            'name' => $krankmeldung->disease->name,
                        ] : null,
                    ] : null,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $children->values(),
            'count' => $children->count(),
        ], 200);
    }

    /**
     * Get comprehensive Care overview.
     *
     * Returns both present children and sick children in a single response.
     *
     * @group Care
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data object The Care overview data.
     * @responseField data.present_children array The list of currently present children.
     * @responseField data.present_count int The count of present children.
     * @responseField data.sick_children array The list of children currently reported sick.
     * @responseField data.sick_count int The count of sick children.
     * @responseField data.timestamp string The timestamp of the data retrieval.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getCareOverview(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has permission to view care data
        if (!$user->can('edit schickzeiten')) {
            return response()->json([
                'success' => false,
                'message' => 'Sie haben keine Berechtigung für diese Aktion.',
            ], 403);
        }

        // Get present children data
        $presentResponse = $this->getPresentChildren($request);
        $presentData = json_decode($presentResponse->getContent(), true);

        // Get sick children data
        $sickResponse = $this->getSickChildren($request);
        $sickData = json_decode($sickResponse->getContent(), true);

        return response()->json([
            'success' => true,
            'data' => [
                'present_children' => $presentData['data'] ?? [],
                'present_count' => $presentData['count'] ?? 0,
                'sick_children' => $sickData['data'] ?? [],
                'sick_count' => $sickData['count'] ?? 0,
                'timestamp' => now()->toIso8601String(),
            ],
        ], 200);
    }
}

