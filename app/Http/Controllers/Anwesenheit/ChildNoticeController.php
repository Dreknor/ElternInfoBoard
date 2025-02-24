<?php

namespace App\Http\Controllers\Anwesenheit;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChildNoticeRequest;
use App\Model\Child;
use App\Model\ChildNotice;
use Illuminate\Support\Facades\Cache;

class ChildNoticeController extends Controller
{



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ChildNoticeRequest $request, Child $child)
    {
        if ($child->parents->contains(auth()->id()) or $child->parents->contains(auth()->user()?->sorg2)) {
            if ($request->notice == null) {
               $childNotice = ChildNotice::where('child_id', $child->id)->where('date', $request->date)->first();
                if ($childNotice) {
                     $childNotice->delete();
                     return response()->json(['message' => 'success'], 200);
                }
            } else {
                try {
                    $childNotice = ChildNotice::firstOrNew([
                        'child_id' => $child->id,
                        'date' => $request->date,
                    ]);
                    $childNotice->fill($request->validated());
                    $childNotice->child_id = $child->id;
                    $childNotice->user_id = auth()->id();
                    $childNotice->save();
                    return response()->json(['message' => 'success'], 200);
                } catch (\Exception $e) {
                    return response()->json(['message' => 'error: ' . $e], 500);
                }
            }
        }

        return response()->json(['message' => 'error: Berechtigung'], 403);

    }

    public function show(Child $child)
    {
        if ($child->parents->contains(auth()->id()) or auth()->user()->sorg2 == $child->user_id) {
            $childNotices = ChildNotice::where('child_id', $child->id)->where('date', today())->first();
            return response()->json($childNotices);
        }

        return response()->json(['message' => 'error: Berechtigung'], 403);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\ChildNotice  $childNotice
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ChildNotice $childNotice)
    {
        if (auth()->id == $childNotice->user_id or auth()->user()->sorg2 == $childNotice->user_id) {
            $childNotice->delete();
            return response()->json(['message' => 'success'], 200);
        }
        return response()->json(['message' => 'error'], 403);
    }

    public function noticeVerwaltung(ChildNoticeRequest $request, Child $child)
    {
        if (auth()->user()->can('edit schickzeiten')) {
            $childNotice = ChildNotice::where('child_id', $child->id)->where('date', $request->date)->updateOrCreate([
                'child_id' => $child->id,
                'date' => $request->date,
            ], [
                'notice' => $request->notice,
                'user_id' => auth()->id()
            ]);

            Cache::forget('notice' . $child->id);


            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Notiz wurde erfolgreich gespeichert'
            ]);
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Keine Berechtigung'
        ]);
    }
}
