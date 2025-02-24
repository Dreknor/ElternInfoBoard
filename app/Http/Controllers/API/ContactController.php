<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\KontaktRequest;
use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Class ContactController
 *
 * Controller for handling contact form related API requests.
 */
class ContactController extends Controller
{
    /**
     * ContactController constructor.
     *
     * Apply authentication middleware.
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of employees.
     *
     * Retrieves a list of employees who have the role 'Mitarbeiter' or the permission 'show in contact form'.
     * Prepends the list with a default 'Sekretariat' entry.
     *
     * @responseField data array The list of employees.
     * @responseField data.id int The ID of the employee.
     * @responseField data.name string The name of the employee.
     * @group Kontakt
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $mitarbeiter = User::whereHas('roles', function ($q) {
            $q->where('name', 'Mitarbeiter');
        })->orWhereHas('permissions', function ($q) {
            $q->where('name', 'show in contact form');
        })->orderBy('name')->get(['id', 'name']);

        $mitarbeiter->prepend(['id' => 0, 'name' => 'Sekretariat']);

        return response()->json(
            ['data' => $mitarbeiter]
        );
    }

    /**
     * Send email.
     *
     * Sends a email to the specified employee or to the default email address if no employee is specified.
     *
     * @bodyParam mitarbeiter int required The ID of the employee to send the email to. If 0, the email is sent to the default address.
     * @bodyParam betreff string required The subject of the email.
     * @bodyParam text string required The content of the email.
     * @responseField success string The success message.
     * @responseField error string The error message.
     * @group Kontakt
     *
     * @param \App\Http\Requests\KontaktRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(KontaktRequest $request)
    {
        if ($request->input('mitarbeiter') != 0) {
            $email = User::query()->where('id', $request->input('mitarbeiter'))->value('email');
        } else {
            $email = config('mail.from.address');
        }

        try {
            Mail::to($email)->send(new SendFeedback($request->input('text'), $request->input('betreff')));

            return response()->json(['success' => 'Mail sent'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Mail not sent'], 500);
        }
    }
}
