<?php

namespace App\Http\Controllers\Elternabfragen;

use App\Http\Controllers\Controller;
use App\Model\Group;
use App\Model\Inquiry;
use App\Model\InquiryAssignment;
use App\Services\Elternabfragen\RecordFilingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PDF für die Schülerakte: einzeln je Kind oder gesammelt je Abfrage bzw. Klasse
 * (nur mit „view elternabfragen answers“, da das PDF Antwortinhalte enthält).
 */
class RecordController extends Controller
{
    public function __construct(private readonly RecordFilingService $filing) {}

    public function assignment(Request $request, InquiryAssignment $assignment): Response
    {
        $inquiry = $assignment->inquiry;
        abort_if($inquiry->purged_at !== null || ! $assignment->isAnswered(), 404);

        return $this->filing->download($inquiry, collect([$assignment]), $request->user(),
            'Akte '.$assignment->childName().' '.$inquiry->title.' v'.$assignment->currentResponse->version);
    }

    public function inquiry(Request $request, Inquiry $inquiry): Response|RedirectResponse
    {
        abort_if($inquiry->purged_at !== null || ! $inquiry->isReleased(), 404);
        $groupId = $request->integer('klasse') ?: null;
        $assignments = $this->filing->assignments($inquiry, $groupId, $request->boolean('nur_neue'));

        if ($assignments->isEmpty()) {
            return back()->with(['type' => 'warning', 'Meldung' => 'Keine vollständigen Antworten für diese Auswahl.']);
        }

        $group = $groupId ? Group::withoutGlobalScopes()->find($groupId)?->name : null;

        return $this->filing->download($inquiry, $assignments, $request->user(), 'Akte '.$inquiry->title.($group ? ' '.$group : ''));
    }
}
