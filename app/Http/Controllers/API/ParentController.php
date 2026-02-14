<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Child;
use App\Model\ChildCheckIn;
use App\Settings\CareSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Validator;

/**
 * Class ParentController
 *
 * Controller for handling parent-related API requests.
 */
class ParentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
    }

    /**
     * Get all children of the authenticated parent and their second guardian (Sorgeberechtigter2).
     *
     * Returns a list of all children associated with the authenticated user
     * and their second guardian (if available).
     *
     * @group Parent
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of children.
     * @responseField data.*.id int The ID of the child.
     * @responseField data.*.first_name string The first name of the child.
     * @responseField data.*.last_name string The last name of the child.
     * @responseField data.*.full_name string The full name of the child.
     * @responseField data.*.group_id int The group ID of the child.
     * @responseField data.*.group object The group information.
     * @responseField data.*.class_id int The class ID of the child.
     * @responseField data.*.class object The class information.
     * @responseField data.*.notification boolean Whether notifications are enabled for the child.
     * @responseField data.*.auto_checkIn boolean Whether auto check-in is enabled for the child.
     * @responseField data.*.is_in_care_module boolean Whether the child belongs to the Care module (group and class are in care settings).
     * @responseField count int The total count of children.
     */
    public function getChildren(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load relations for better performance
        $user->load(['children_rel.group', 'children_rel.class', 'sorgeberechtigter2.children_rel.group', 'sorgeberechtigter2.children_rel.class']);

        // Get all children (including those from sorgeberechtigter2)
        $children = $user->children();

        // If no children found
        if (is_null($children) || $children->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
            ], 200);
        }

        // Get care settings to check if child belongs to care module
        $careSettings = new CareSetting;
        $careGroups = $careSettings->groups_list ?? [];
        $careClasses = $careSettings->class_list ?? [];

        // Map children data
        $childrenData = $children->map(function ($child) use ($careGroups, $careClasses) {
            // Check if child is in care module (both group and class must be in care settings)
            $isInCareModule = in_array($child->group_id, $careGroups) && in_array($child->class_id, $careClasses);

            return [
                'id' => $child->id,
                'first_name' => $child->first_name,
                'last_name' => $child->last_name,
                'full_name' => $child->first_name . ' ' . $child->last_name,
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
                'notification' => $child->notification ?? false,
                'auto_checkIn' => $child->auto_checkIn ?? false,
                'is_in_care_module' => $isInCareModule,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $childrenData->values(),
            'count' => $childrenData->count(),
        ], 200);
    }

    /**
     * Get check-in status for all children of the authenticated parent in the Care module.
     *
     * Returns the check-in status for today and upcoming days for children in the Care module.
     *
     * @group Parent
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of children with their check-in status.
     * @responseField data.*.child_id int The ID of the child.
     * @responseField data.*.child_name string The full name of the child.
     * @responseField data.*.is_in_care_module boolean Whether the child belongs to the Care module.
     * @responseField data.*.check_ins array The check-in records for the child.
     * @responseField data.*.check_ins.*.id int The ID of the check-in record.
     * @responseField data.*.check_ins.*.date string The date of the check-in.
     * @responseField data.*.check_ins.*.checked_in boolean Whether the child is checked in.
     * @responseField data.*.check_ins.*.checked_out boolean Whether the child is checked out.
     * @responseField data.*.check_ins.*.should_be boolean Whether the child should be present.
     * @responseField data.*.check_ins.*.lock_at string The date when the check-in is locked.
     * @responseField data.*.check_ins.*.comment string Comment for the check-in.
     * @responseField data.*.check_ins.*.check_in_time string The timestamp when the child was checked in (ISO 8601 format).
     * @responseField data.*.check_ins.*.check_out_time string The timestamp when the child was checked out (ISO 8601 format).
     * @responseField data.*.check_ins.*.can_edit boolean Whether the parent can still edit this check-in.
     */
    public function getChildrenCheckInStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load children with their relations
        $user->load(['children_rel.group', 'children_rel.class', 'sorgeberechtigter2.children_rel.group', 'sorgeberechtigter2.children_rel.class']);

        $children = $user->children();

        if (is_null($children) || $children->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
            ], 200);
        }

        // Get care settings
        $careSettings = new CareSetting;
        $careGroups = $careSettings->groups_list ?? [];
        $careClasses = $careSettings->class_list ?? [];

        // Filter children that belong to care module
        $careChildren = $children->filter(function ($child) use ($careGroups, $careClasses) {
            return in_array($child->group_id, $careGroups) && in_array($child->class_id, $careClasses);
        });

        if ($careChildren->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
                'message' => 'Keine Kinder im Care-Modul gefunden.',
            ], 200);
        }

        // Load check-ins for these children
        $childIds = $careChildren->pluck('id')->toArray();

        $checkIns = ChildCheckIn::query()
            ->whereIn('child_id', $childIds)
            ->where('date', '>=', today())
            ->orderBy('date')
            ->select('id', 'child_id', 'date', 'checked_in', 'checked_out', 'should_be', 'lock_at', 'comment', 'created_at', 'updated_at')
            ->get()
            ->groupBy('child_id');

        $data = $careChildren->map(function ($child) use ($checkIns) {
            $childCheckIns = $checkIns->get($child->id, collect());

            return [
                'child_id' => $child->id,
                'child_name' => $child->first_name . ' ' . $child->last_name,
                'is_in_care_module' => true,
                'check_ins' => $childCheckIns->map(function ($checkIn) {
                    $canEdit = is_null($checkIn->lock_at) || $checkIn->lock_at->endOfDay() >= now();

                    // Determine check-in and check-out timestamps
                    // When checked_in changes to true, created_at or updated_at represents the check-in time
                    // When checked_out changes to true, updated_at represents the check-out time
                    $checkInTime = null;
                    $checkOutTime = null;

                    if ($checkIn->checked_in) {
                        // Use created_at as initial check-in time
                        $checkInTime = $checkIn->created_at?->toIso8601String();
                    }

                    if ($checkIn->checked_out) {
                        // Use updated_at as check-out time (when status changed to checked_out)
                        $checkOutTime = $checkIn->updated_at?->toIso8601String();
                    }

                    return [
                        'id' => $checkIn->id,
                        'date' => $checkIn->date->toDateString(),
                        'checked_in' => $checkIn->checked_in,
                        'checked_out' => $checkIn->checked_out,
                        'should_be' => $checkIn->should_be,
                        'lock_at' => $checkIn->lock_at?->toDateString(),
                        'comment' => $checkIn->comment,
                        'check_in_time' => $checkInTime,
                        'check_out_time' => $checkOutTime,
                        'can_edit' => $canEdit,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Confirm that a child should be present (Anwesenheit bestätigen).
     *
     * @group Parent
     *
     * @urlParam checkInId int required The ID of the check-in record. Example: 1
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField message string A message describing the result.
     * @responseField data object The updated check-in record.
     */
    public function confirmAttendance(Request $request, int $checkInId): JsonResponse
    {
        $user = $request->user();

        $checkIn = ChildCheckIn::find($checkInId);

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'Check-In nicht gefunden.',
            ], 404);
        }

        // Check if user owns this child
        $children = $user->children();
        if (is_null($children) || !$children->contains($checkIn->child)) {
            return response()->json([
                'success' => false,
                'message' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ], 403);
        }

        // Check if check-in is locked
        if ($checkIn->lock_at && $checkIn->lock_at->endOfDay() < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Anwesenheit kann nicht mehr geändert werden.',
            ], 422);
        }

        $checkIn->update([
            'should_be' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anwesenheit wurde bestätigt.',
            'data' => [
                'id' => $checkIn->id,
                'date' => $checkIn->date->toDateString(),
                'should_be' => $checkIn->should_be,
                'checked_in' => $checkIn->checked_in,
                'checked_out' => $checkIn->checked_out,
            ],
        ], 200);
    }

    /**
     * Decline that a child will be present (Anwesenheit ablehnen).
     *
     * @group Parent
     *
     * @urlParam checkInId int required The ID of the check-in record. Example: 1
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField message string A message describing the result.
     * @responseField data object The updated check-in record.
     */
    public function declineAttendance(Request $request, int $checkInId): JsonResponse
    {
        $user = $request->user();

        $checkIn = ChildCheckIn::find($checkInId);

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'Check-In nicht gefunden.',
            ], 404);
        }

        // Check if user owns this child
        $children = $user->children();
        if (is_null($children) || !$children->contains($checkIn->child)) {
            return response()->json([
                'success' => false,
                'message' => 'Sie können nur Ihre eigenen Kinder bearbeiten.',
            ], 403);
        }

        // Check if check-in is locked
        if ($checkIn->lock_at && $checkIn->lock_at->endOfDay() < now()) {
            return response()->json([
                'success' => false,
                'message' => 'Anwesenheit kann nicht mehr geändert werden.',
            ], 422);
        }

        $checkIn->update([
            'should_be' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anwesenheit wurde abgelehnt.',
            'data' => [
                'id' => $checkIn->id,
                'date' => $checkIn->date->toDateString(),
                'should_be' => $checkIn->should_be,
                'checked_in' => $checkIn->checked_in,
                'checked_out' => $checkIn->checked_out,
            ],
        ], 200);
    }

    /**
     * Get Schickzeiten (sending times) for the authenticated parent's children.
     *
     * Returns the scheduled sending times for children in the Care module.
     *
     * @group Parent
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of children with their Schickzeiten.
     * @responseField data.*.child_id int The ID of the child.
     * @responseField data.*.child_name string The full name of the child.
     * @responseField data.*.schickzeiten array The scheduled times for the child.
     * @responseField data.*.schickzeiten.*.id int The ID of the Schickzeit record.
     * @responseField data.*.schickzeiten.*.weekday int The day of the week (1-5).
     * @responseField data.*.schickzeiten.*.specific_date string The specific date (if set).
     * @responseField data.*.schickzeiten.*.type string The type of time (genau, ab, etc.).
     * @responseField data.*.schickzeiten.*.time string The time.
     */
    public function getSchickzeiten(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load children with their relations
        $user->load(['children_rel.group', 'children_rel.class', 'children_rel.schickzeiten', 'sorgeberechtigter2.children_rel.group', 'sorgeberechtigter2.children_rel.class', 'sorgeberechtigter2.children_rel.schickzeiten']);

        $children = $user->children();

        if (is_null($children) || $children->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
            ], 200);
        }

        // Get care settings
        $careSettings = new CareSetting;
        $careGroups = $careSettings->groups_list ?? [];
        $careClasses = $careSettings->class_list ?? [];

        // Filter children that belong to care module
        $careChildren = $children->filter(function ($child) use ($careGroups, $careClasses) {
            return in_array($child->group_id, $careGroups) && in_array($child->class_id, $careClasses);
        });

        if ($careChildren->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
                'message' => 'Keine Kinder im Care-Modul gefunden.',
            ], 200);
        }

        $weekdayNames = [
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
        ];

        $data = $careChildren->map(function ($child) use ($weekdayNames) {
            return [
                'child_id' => $child->id,
                'child_name' => $child->first_name . ' ' . $child->last_name,
                'schickzeiten' => $child->schickzeiten->map(function ($schickzeit) use ($weekdayNames) {
                    return [
                        'id' => $schickzeit->id,
                        'weekday' => $schickzeit->weekday,
                        'weekday_name' => $weekdayNames[$schickzeit->weekday] ?? null,
                        'specific_date' => $schickzeit->specific_date?->toDateString(),
                        'type' => $schickzeit->type,
                        'time' => $schickzeit->time,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Get active sick reports (Krankmeldungen) for the authenticated parent's children.
     *
     * Returns all active and future sick reports for children.
     *
     * @group Parent
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of children with active sick reports.
     * @responseField data.*.child_id int The ID of the child.
     * @responseField data.*.child_name string The full name of the child.
     * @responseField data.*.krankmeldungen array The sick reports for the child.
     * @responseField data.*.krankmeldungen.*.id int The ID of the sick report.
     * @responseField data.*.krankmeldungen.*.name string The name/reason of the sick report.
     * @responseField data.*.krankmeldungen.*.kommentar string The comment.
     * @responseField data.*.krankmeldungen.*.start string The start date.
     * @responseField data.*.krankmeldungen.*.ende string The end date.
     * @responseField data.*.krankmeldungen.*.disease object The disease information (if available).
     */
    public function getKrankmeldungen(Request $request): JsonResponse
    {
        $user = $request->user();

        // Load children with their relations
        $user->load([
            'children_rel.krankmeldungen' => function ($query) {
                $query->whereDate('ende', '>=', today())
                    ->orderByDesc('created_at')
                    ->with('disease:id,name');
            },
            'sorgeberechtigter2.children_rel.krankmeldungen' => function ($query) {
                $query->whereDate('ende', '>=', today())
                    ->orderByDesc('created_at')
                    ->with('disease:id,name');
            },
        ]);

        $children = $user->children();

        if (is_null($children) || $children->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
            ], 200);
        }

        // Filter children with active sick reports
        $childrenWithKrankmeldungen = $children->filter(function ($child) {
            return $child->krankmeldungen && $child->krankmeldungen->isNotEmpty();
        });

        $data = $childrenWithKrankmeldungen->map(function ($child) {
            return [
                'child_id' => $child->id,
                'child_name' => $child->first_name . ' ' . $child->last_name,
                'krankmeldungen' => $child->krankmeldungen->map(function ($krankmeldung) {
                    return [
                        'id' => $krankmeldung->id,
                        'name' => $krankmeldung->name,
                        'kommentar' => $krankmeldung->kommentar,
                        'start' => $krankmeldung->start?->toDateString(),
                        'ende' => $krankmeldung->ende?->toDateString(),
                        'disease' => $krankmeldung->disease ? [
                            'id' => $krankmeldung->disease->id,
                            'name' => $krankmeldung->disease->name,
                        ] : null,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }

    /**
     * Get historical/past sick reports (Krankmeldungen) for the authenticated parent's children.
     *
     * Returns all past sick reports for children (ended before today).
     *
     * @group Parent
     *
     * @queryParam limit int The maximum number of records to return per child. Defaults to 50. Example: 20
     * @queryParam child_id int Filter by specific child ID. Example: 5
     *
     * @responseField success boolean Whether the request was successful.
     * @responseField data array The list of children with past sick reports.
     * @responseField data.*.child_id int The ID of the child.
     * @responseField data.*.child_name string The full name of the child.
     * @responseField data.*.krankmeldungen array The past sick reports for the child.
     * @responseField data.*.krankmeldungen.*.id int The ID of the sick report.
     * @responseField data.*.krankmeldungen.*.name string The name/reason of the sick report.
     * @responseField data.*.krankmeldungen.*.kommentar string The comment.
     * @responseField data.*.krankmeldungen.*.start string The start date.
     * @responseField data.*.krankmeldungen.*.ende string The end date.
     * @responseField data.*.krankmeldungen.*.disease object The disease information (if available).
     * @responseField data.*.krankmeldungen.*.created_at string When the sick report was created.
     * @responseField count int The total count of children with past sick reports.
     */
    public function getKrankmeldungenHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate query parameters
        $validator = Validator::make($request->all(), [
            'limit' => 'sometimes|integer|min:1|max:200',
            'child_id' => 'sometimes|integer|exists:children,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierungsfehler.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $limit = $request->input('limit', 50);
        $childIdFilter = $request->input('child_id');

        // Load children with their relations
        $user->load([
            'children_rel.krankmeldungen' => function ($query) use ($limit) {
                $query->whereDate('ende', '<', today())
                    ->orderByDesc('ende')
                    ->limit($limit)
                    ->with('disease:id,name');
            },
            'sorgeberechtigter2.children_rel.krankmeldungen' => function ($query) use ($limit) {
                $query->whereDate('ende', '<', today())
                    ->orderByDesc('ende')
                    ->limit($limit)
                    ->with('disease:id,name');
            },
        ]);

        $children = $user->children();

        if (is_null($children) || $children->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'count' => 0,
            ], 200);
        }

        // Filter by specific child if requested
        if ($childIdFilter) {
            $children = $children->filter(function ($child) use ($childIdFilter) {
                return $child->id == $childIdFilter;
            });

            if ($children->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kind nicht gefunden oder Sie haben keine Berechtigung.',
                ], 404);
            }
        }

        // Filter children with past sick reports
        $childrenWithKrankmeldungen = $children->filter(function ($child) {
            return $child->krankmeldungen && $child->krankmeldungen->isNotEmpty();
        });

        $data = $childrenWithKrankmeldungen->map(function ($child) {
            return [
                'child_id' => $child->id,
                'child_name' => $child->first_name . ' ' . $child->last_name,
                'krankmeldungen' => $child->krankmeldungen->map(function ($krankmeldung) {
                    return [
                        'id' => $krankmeldung->id,
                        'name' => $krankmeldung->name,
                        'kommentar' => $krankmeldung->kommentar,
                        'start' => $krankmeldung->start?->toDateString(),
                        'ende' => $krankmeldung->ende?->toDateString(),
                        'disease' => $krankmeldung->disease ? [
                            'id' => $krankmeldung->disease->id,
                            'name' => $krankmeldung->disease->name,
                        ] : null,
                        'created_at' => $krankmeldung->created_at?->toIso8601String(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'count' => $data->count(),
        ], 200);
    }
}




