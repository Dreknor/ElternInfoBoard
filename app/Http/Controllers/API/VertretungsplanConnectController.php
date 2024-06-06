<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiDeleteVertretungsNewsRequest;
use App\Http\Requests\ApiDeleteVertretungsRequest;
use App\Http\Requests\ApiImportVertretungsNewsRequest;
use App\Http\Requests\ApiImportVertretungsRequest;
use App\Http\Requests\ApiImportVertretungsWeekRequest;
use App\Model\Group;
use App\Model\User;
use App\Model\Vertretung;
use App\Model\VertretungsplanAbsence;
use App\Model\VertretungsplanNews;
use App\Model\VertretungsplanWeek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class VertretungsplanConnectController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
    }

    public function store(ApiImportVertretungsRequest $request)
    {

        $group = Group::where('name', $request->get('klasse'))->first();

        if (!$group) {
            return response()->json([
                'error' => 'Klasse nicht gefunden'
            ], 404);
        }

        $data = $request->all();
        $vertretung = new Vertretung();
        $vertretung->id = $data['id'];
        $vertretung->date = $data['date'];
        $vertretung->klasse = $group->id;
        $vertretung->stunde = $data['stunde'];
        $vertretung->altFach = $data['altFach'];
        $vertretung->neuFach = $data['neuFach'];
        $vertretung->lehrer = $data['lehrer'];
        $vertretung->comment = $data['comment'];
        $vertretung->save();
        return response()->json([
            'success' => 'Vertretung erfolgreich gespeichert'
        ]);
    }

    public function update(ApiImportVertretungsRequest $request, $id)
    {
        $group = Group::where('name', $request->get('klasse'))->first();

        if (!$group) {
            return response()->json([
                'error' => 'Klasse nicht gefunden'
            ], 404);
        }

        $data = $request->all();
        $vertretung = Vertretung::find($id);


        if (!$vertretung) {
            return response()->json([
                'error' => 'Vertretung nicht gefunden'
            ], 404);
        }


        $vertretung->date = $data['date'];
        $vertretung->klasse = $group->id;
        $vertretung->stunde = $data['stunde'];
        $vertretung->altFach = $data['altFach'];
        $vertretung->neuFach = $data['neuFach'];
        $vertretung->lehrer = $data['lehrer'];
        $vertretung->comment = $data['comment'];
        $vertretung->save();
        return response()->json([
            'success' => 'Vertretung erfolgreich aktualisiert'
        ]);
    }

    public function destroy(ApiDeleteVertretungsRequest $request, $id)
    {
        $vertretung = Vertretung::find($id);

        if (!$vertretung) {
            return response()->json([
                'error' => 'Vertretung nicht gefunden'
            ], 404);
        }

        $vertretung->delete();


        return response()->json([
            'success' => 'Vertretung erfolgreich gelöscht'
        ]);
    }


    public function storeNews(ApiImportVertretungsNewsRequest $request)
    {
        $data = $request->validated();
        $news = new VertretungsplanNews();
        $news->id = $data['id'];
        $news->start = $data['start'];
        $news->end = $data['end'];
        $news->news = $data['news'];
        $news->save();
        return response()->json([
            'success' => 'News erfolgreich gespeichert'
        ]);
    }

    public function deleteNews(ApiDeleteVertretungsNewsRequest $request, $id)
    {
        $news = VertretungsplanNews::find($id);

        if (!$news) {
            return response()->json([
                'error' => 'News nicht gefunden'
            ], 404);
        }

        $news->delete();
    }

    public function storeWeek(ApiImportVertretungsWeekRequest $request)
    {
        $week = new VertretungsplanWeek($request->validated());
        $week->save();

        return response()->json([
            'success' => 'Vertretungen erfolgreich gespeichert'
        ]);

    }

    public function updateWeek(ApiImportVertretungsWeekRequest $request, $id)
    {
        $week = VertretungsplanWeek::firstOrNew(['id' => $id]);
        $week->fill($request->validated());
        $week->id = $id;
        $week->save();

        return response()->json([
            'success' => 'Vertretungen erfolgreich aktualisiert'
        ]);
    }

    public function deleteWeek(Request $request, $id)
    {
        $week = VertretungsplanWeek::find($id);

        if (!$week) {
            return response()->json([
                'error' => 'Vertretungen nicht gefunden'
            ], 404);
        }

        $week->delete();

        return response()->json([
            'success' => 'Vertretungen erfolgreich gelöscht'
        ]);
    }
 public function storeAbsence(Request $request)
    {

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'name' => 'required|string',
            'reason' => 'nullable|string',
            'id' => 'required|integer'
        ]);

        $absence = new VertretungsplanAbsence([
            'start_date' => $request->get('start_date'),
            'end_date' => $request->get('end_date'),
            'name' => $request->get('name'),
            'reason' => $request->get('reason'),
            'absence_id' => $request->get('id')
        ]);
        $absence->save();

        return response()->json([
            'success' => 'Abwesenheit erfolgreich gespeichert'
        ]);

    }

    public function updateAbsence(Request $request, $id)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'name' => 'required|string',
            'reason' => 'nullable|string'
        ]);

        $week = VertretungsplanAbsence::firstOrNew(['id' => $id]);
        $week->fill($request->validated());
        $week->id = $id;
        $week->save();

        return response()->json([
            'success' => 'Vertretungen erfolgreich aktualisiert'
        ]);
    }

    public function deleteAbsence(Request $request, $id)
    {
        $absence = VertretungsplanAbsence::where('absence_id', $id)->first();

        if (!$absence) {
            return response()->json([
                'error' => 'Abwesenheit nicht gefunden'
            ], 404);
        }

        $absence->delete();

        return response()->json([
            'success' => 'Abwesenheit erfolgreich gelöscht'
        ]);
    }

}
