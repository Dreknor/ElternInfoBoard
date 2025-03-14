<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Vertretung;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanNews;
use App\Model\VertretungsplanWeek;
use Carbon\Carbon;
use Illuminate\Http\Request;

    /** Class VertretungsplanController
     *
     * Controller for handling Vertretungsplan (substitution plan) related API requests.
     **/
class VertretungsplanController extends Controller
{
    /**
     * VertretungsplanController constructor.
     *
     * Apply authentication middleware.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display the Vertretungsplan.
     *
     * This method returns the Vertretungsplan for the current user.
     * The user must be authenticated and have the permission to view the Vertretungsplan.
     * The user can only view the Vertretungsplan for their own classes unless they have the permission to view all Vertretungsplan entries.
     * The method returns a JSON response with the Vertretungsplan entries, news, the current week, and absences for the current week.
     * The Vertretungsplan entries are ordered by date and hour.
     * The news are ordered by date.
     * The absences are filtered by the current week.
     *
     * The method returns a 401 response if the user is not authenticated.
     * The method returns a 403 response if the user does not have the permission to view the Vertretungsplan.
     *
     * @authenticated
     * @group Vertretungsplan
     *
     * @responseField vertretungen array The Vertretungsplan entries.
     * @responseField news array The news entries.
     * @responseField week object The current week.
     * @responseField absences array The absences for the current week.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user){
            return response()->json([
                'message' => 'Sie sind nicht angemeldet.'
            ], 401);

        }

        if (!$user->hasPermissionTo('view vertretungsplan', 'web')) {
            return response()->json([
                'message' => 'Sie haben keine Berechtigung, den Vertretungsplan anzuzeigen.'
            ], 403);
        }


        if ($user->hasPermissionTo('view vertretungsplan all', 'web')) {
            $vertretungen = Vertretung::orderBy('date', 'desc')->orderBy('stunde')->get([
                'date',
                'klasse',
                'stunde',
                'altFach',
                'neuFach',
                'lehrer',
                'comment'
            ]);
        } else {
            $vertretungen = $user->vertretungen()->orderBy('stunde', 'asc')->get([
                'date',
                'klasse',
                'stunde',
                'altFach',
                'neuFach',
                'lehrer',
                'comment'
            ]);
        }

        $news = VertretungsplanNews::where('end', '>=', Carbon::now())->get(['start', 'end', 'news']);

        $week = VertretungsplanWeek::where('week', Carbon::now()->startOfWeek()->format('Y-m-d'))->first(['type', 'week']);

        $absences = VertretungsplanAbsence::query()
            ->where('start_date', '>=', Carbon::now()->startOfWeek())
            ->where('end_date', '<=', Carbon::now()->endOfWeek())
            ->get([
                'name',
                'start_date',
                'end_date',
                'reason'
            ]);


        return response()->json([
            'vertretungen' => $vertretungen,
            'news' => $news,
            'week' => $week,
            'absences' => $absences
        ], 200);
    }
}
