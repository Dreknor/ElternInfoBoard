<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreatePflichtstundeRequest;
use App\Model\Pflichtstunde;
use App\Settings\PflichtstundenSetting;
use Illuminate\Http\Request;

class PflichtstundeController extends Controller
{
    protected PflichtstundenSetting $pflichtstunden_settings;
    public function __construct()
    {
        $this->middleware('auth');
        $this->pflichtstunden_settings = new PflichtstundenSetting();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (!auth()->user()->can('view Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunden = auth()->user()->pflichtstunden;
        return view('pflichtstunden.index', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePflichtstundeRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        Pflichtstunde::create($data);
        return redirect()->route('pflichtstunden.index')->with('success', 'Pflichtstunde angelegt');
    }

    /**
     * Verwaltungsansicht der Pflichtstunden
     */

    public function verwaltungIndex(){
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunden = Pflichtstunde::with('user')->get();
        return view('pflichtstunden.indexVerwaltung', [
            'pflichtstunden' => $pflichtstunden,
            'pflichtstunden_settings' => $this->pflichtstunden_settings
        ]);
    }

    public function approve(Pflichtstunde $pflichtstunde)
    {
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $pflichtstunde->approved = true;
        $pflichtstunde->approved_at = now();
        $pflichtstunde->approved_by = auth()->id();
        $pflichtstunde->rejected = false;
        $pflichtstunde->rejected_at = null;
        $pflichtstunde->rejected_by = null;
        $pflichtstunde->rejection_reason = null;
        $pflichtstunde->save();

        return redirect()->route('pflichtstunden.indexVerwaltung')->with('success', 'Pflichtstunde genehmigt');
    }

    public function reject(Request $request, Pflichtstunde $pflichtstunde)
    {
        if (!auth()->user()->can('edit Pflichtstunden')) {
            return redirect(url('/'))->with('error', 'Berechtigung fehlt');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:255',
        ]);

        $pflichtstunde->approved = false;
        $pflichtstunde->approved_at = null;
        $pflichtstunde->approved_by = null;
        $pflichtstunde->rejected = true;
        $pflichtstunde->rejected_at = now();
        $pflichtstunde->rejected_by = auth()->id();
        $pflichtstunde->rejection_reason = $request->input('rejection_reason');
        $pflichtstunde->save();

        return redirect()->route('pflichtstunden.indexVerwaltung')->with('success', 'Pflichtstunde abgelehnt');
    }
}
