<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Model\Conversation;
use App\Services\Students\StudentMessenger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Lehrkraft beendet einen Schülerchat (S-14). Danach kann der Schüler nicht mehr schreiben. */
class TeacherStudentChatController extends Controller
{
    public function close(Request $request, Conversation $conversation, StudentMessenger $messenger): RedirectResponse
    {
        $messenger->close($request->user(), $conversation);

        return redirect()->route('messenger.index')->with(['type' => 'success', 'Meldung' => 'Chat beendet. Die Schülerin oder der Schüler kann nicht mehr schreiben.']);
    }

    public function apiClose(Request $request, Conversation $conversation, StudentMessenger $messenger): JsonResponse
    {
        $messenger->close($request->user(), $conversation);

        return response()->json(['message' => 'Chat beendet.']);
    }
}
