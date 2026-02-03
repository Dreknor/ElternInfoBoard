<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use App\Model\ActiveDisease;
use App\Model\Disease;
use Illuminate\Http\Request;

class DiseaseController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'permission:manage diseases',
        ];
    }

    /**
     * Display the disease management page (combining active diseases and master data).
     */
    public function index()
    {
        $diseases = Disease::all();
        $activeDiseases = ActiveDisease::with('disease', 'user')
            ->whereDate('start', '<=', now())
            ->whereDate('end', '>=', now())
            ->orderBy('active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('krankmeldung.diseases.manage', compact('diseases', 'activeDiseases'));
    }

    /**
     * Store a newly created disease in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:diseases,name',
            'reporting' => 'required|boolean',
            'wiederzulassung_durch' => 'required|string|max:255',
            'wiederzulassung_wann' => 'required|string|max:255',
            'aushang_dauer' => 'required|integer|min:1',
        ]);

        Disease::create($validated);

        return redirect()->route('diseases.index')->with([
            'Meldung' => 'Krankheit wurde erfolgreich erstellt',
            'type' => 'success',
        ]);
    }

    /**
     * Update the specified disease in storage.
     */
    public function update(Request $request, Disease $disease)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:diseases,name,'.$disease->id,
            'reporting' => 'required|boolean',
            'wiederzulassung_durch' => 'required|string|max:255',
            'wiederzulassung_wann' => 'required|string|max:255',
            'aushang_dauer' => 'required|integer|min:1',
        ]);

        $disease->update($validated);

        return redirect()->route('diseases.index')->with([
            'Meldung' => 'Krankheit wurde erfolgreich aktualisiert',
            'type' => 'success',
        ]);
    }

    /**
     * Remove the specified disease from storage.
     */
    public function destroy(Disease $disease)
    {
        // Check if disease is used in active diseases
        if ($disease->activeDiseases()->count() > 0) {
            return redirect()->back()->with([
                'Meldung' => 'Diese Krankheit kann nicht gelöscht werden, da sie bereits verwendet wird',
                'type' => 'danger',
            ]);
        }

        $disease->delete();

        return redirect()->route('diseases.index')->with([
            'Meldung' => 'Krankheit wurde erfolgreich gelöscht',
            'type' => 'success',
        ]);
    }
}
