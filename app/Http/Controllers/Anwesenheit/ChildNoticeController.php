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
        // Formular auf der Startseite (ohne JavaScript): zurück mit Meldung statt JSON.
        $redirectBack = $request->boolean('redirect_back');

        if (auth()->user()->cannot('manage', $child)) {
            return $redirectBack
                ? redirect()->back()->with(['type' => 'warning', 'Meldung' => 'Sie können nur Ihre eigenen Kinder bearbeiten.'])
                : response()->json(['message' => 'error: Berechtigung'], 403);
        }

        if ($request->notice == null) {
            ChildNotice::where('child_id', $child->id)->whereDate('date', $request->date)->delete();
            Cache::forget('notice'.$child->id);

            return $redirectBack
                ? redirect()->back()->with(['type' => 'success', 'Meldung' => 'Notiz wurde entfernt.'])
                : response()->json(['message' => 'success'], 200);
        }

        try {
            $childNotice = ChildNotice::firstOrNew([
                'child_id' => $child->id,
                'date' => $request->date,
            ]);
            $childNotice->fill($request->validated());
            $childNotice->child_id = $child->id;
            $childNotice->user_id = auth()->id();
            $childNotice->save();
            Cache::forget('notice'.$child->id);
        } catch (\Exception $e) {
            report($e);

            return $redirectBack
                ? redirect()->back()->with(['type' => 'danger', 'Meldung' => 'Die Notiz konnte nicht gespeichert werden.'])
                : response()->json(['message' => 'error'], 500);
        }

        return $redirectBack
            ? redirect()->back()->with(['type' => 'success', 'Meldung' => 'Notiz wurde gespeichert.'])
            : response()->json(['message' => 'success'], 200);
    }

    public function show(Child $child)
    {
        if (auth()->user()->can('view', $child)) {
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
        if ($childNotice->child && auth()->user()->can('manage', $childNotice->child)) {
            $childNotice->delete();

            return response()->json(['message' => 'success'], 200);
        }

        return response()->json(['message' => 'error'], 403);
    }

    public function noticeVerwaltung(ChildNoticeRequest $request, Child $child)
    {
        if (auth()->user()->can('edit schickzeiten')) {
            $childNotice = ChildNotice::where('child_id', $child->id)->where('date', $request->date)->first();

            if ($childNotice && $request->notice != null) {
                $childNotice->fill($request->validated());
                $childNotice->save();
            } elseif ($childNotice && $request->notice == null) {
                $childNotice->delete();
            } elseif ($request->notice != null) {
                $childNotice = new ChildNotice;
                $childNotice->fill($request->validated());
                $childNotice->child_id = $child->id;
                $childNotice->user_id = auth()->id();
                $childNotice->save();
            }

            Cache::forget('notice'.$child->id);

            return redirect()->back()->with([
                'type' => 'success',
                'Meldung' => 'Notiz wurde erfolgreich gespeichert',
            ]);
        }

        return redirect()->back()->with([
            'type' => 'danger',
            'Meldung' => 'Keine Berechtigung',
        ]);
    }
}
