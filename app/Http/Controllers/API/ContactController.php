<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\KontaktRequest;
use App\Jobs\SendKontaktMail;
use App\Model\User;
use App\Settings\EmailSetting;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class ContactController
 *
 * Controller for handling contact form related API requests.
 */
class ContactController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth:sanctum',
        ];
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
     *
     * @group Kontakt
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $emailSettings = new EmailSetting;
        $defaultName = $emailSettings->contact_default_name ?? 'Sekretariat';

        $mitarbeiter = User::whereHas('roles', function ($q) {
            $q->where('name', 'Mitarbeiter');
        })->orWhereHas('permissions', function ($q) {
            $q->where('name', 'show in contact form');
        })->orderBy('name')->get(['id', 'name']);

        $mitarbeiter->prepend(['id' => 0, 'name' => $defaultName]);

        return response()->json(
            ['data' => $mitarbeiter]
        );
    }

    /**
     * Send email.
     *
     * Queues an email to the specified employee or to the default email address if no employee is specified.
     * Attachments are stored privately until the mail has been sent.
     *
     * @bodyParam mitarbeiter int required The ID of the employee to send the email to. If 0, the email is sent to the default address.
     * @bodyParam betreff string required The subject of the email.
     * @bodyParam text string required The content of the email.
     * @bodyParam files file[] Up to 3 attachments, max. 8 MB each.
     *
     * @responseField success string The success message.
     * @responseField error string The error message.
     *
     * @group Kontakt
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(KontaktRequest $request)
    {
        $user = $request->user();
        $recipientId = (int) $request->input('mitarbeiter');

        if ($recipientId !== 0) {
            // Nur Empfänger aus der Kontaktliste – nicht beliebige Nutzer-IDs.
            $email = $this->contactQuery()->where('id', $recipientId)->value('email');
            if (! $email) {
                return response()->json(['error' => 'Empfänger nicht gefunden', 'message' => 'Empfänger nicht gefunden'], 404);
            }
        } else {
            $emailSettings = new EmailSetting;
            $email = $emailSettings->contact_default_email ?: config('mail.from.address');
        }

        // Anhänge bis zum Versand privat ablegen; der Job verschickt die Mail im Hintergrund,
        // damit die App nicht auf Upload-Verarbeitung und SMTP-Versand warten muss.
        $attachments = [];
        $directory = null;
        foreach ((array) $request->file('files', []) as $file) {
            if ($file && $file->isValid()) {
                $directory ??= SendKontaktMail::DIRECTORY.'/'.Str::uuid();
                $path = $file->storeAs($directory, count($attachments).'_'.Str::random(8), 'local');
                if ($path === false) {
                    Storage::disk('local')->deleteDirectory($directory);

                    return response()->json([
                        'error' => 'Attachment not stored',
                        'message' => 'Der Anhang konnte nicht gespeichert werden. Bitte versuchen Sie es später erneut.',
                    ], 500);
                }
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }

        // Keine Inhalte protokollieren (B-61) – nur Metadaten.
        Log::info('Kontaktformular (API)', [
            'user_id' => $user?->id,
            'recipient_id' => $recipientId,
            'attachments' => count($attachments),
        ]);

        SendKontaktMail::dispatch(
            $email,
            $request->input('text'),
            $request->input('betreff'),
            $attachments,
            $directory,
            $user?->name,
            $user?->email,
        );

        return response()->json(['success' => 'Mail sent'], 200);
    }

    /** Nutzer, die im Kontaktformular auswählbar sind. */
    private function contactQuery()
    {
        return User::query()->where(function ($q) {
            $q->whereHas('roles', fn ($r) => $r->where('name', 'Mitarbeiter'))
                ->orWhereHas('permissions', fn ($p) => $p->where('name', 'show in contact form'));
        });
    }
}
