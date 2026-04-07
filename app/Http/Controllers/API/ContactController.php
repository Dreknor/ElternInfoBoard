<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\KontaktRequest;
use App\Mail\SendFeedback;
use App\Model\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        // Log entry point BEFORE anything else
        \Illuminate\Support\Facades\Log::channel('single')->info('=== CONTACT CONTROLLER SEND METHOD CALLED ===', [
            'timestamp' => now()->toDateTimeString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'all_input' => $request->all(),
        ]);

        Log::info('ContactController: send() method called', [
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'text_length' => strlen($request->input('text')),
            'betreff' => $request->input('betreff'),
            'mitarbeiterId' => $request->input('mitarbeiter'),
            'has_files' => $request->hasFile('files'),
            'files_count' => $request->hasFile('files') ? count($request->file('files')) : 0,
        ]);

        // Determine recipient email
        if ($request->input('mitarbeiter') != 0) {
            $email = User::query()->where('id', $request->input('mitarbeiter'))->value('email');
            if (!$email) {
                Log::warning('User email not found for mitarbeiter ID: ' . $request->input('mitarbeiter'));
                return response()->json(['error' => 'Empfänger nicht gefunden'], 404);
            }
        } else {
            $email = config('mail.from.address');
        }

        Log::info('Target email address: ' . $email);

        // Prepare attachments data
        $attachmentsData = [];
        if ($request->hasFile('files')) {
            $files = $request->file('files');
            Log::info('Processing ' . count($files) . ' file attachment(s)');

            foreach ($files as $index => $file) {
                if ($file && $file->isValid()) {
                    Log::info('File attachment ' . ($index + 1) . ':', [
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ]);

                    // Store file temporarily for email attachment
                    $attachmentsData[] = [
                        'path' => $file->getRealPath(),
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ];
                } else {
                    Log::warning('Invalid file at index ' . $index);
                }
            }
        }

        try {
            $user = $request->user();

            Log::info('Attempting to send email', [
                'to' => $email,
                'from_user' => $user?->name,
                'reply_to' => $user?->email,
                'attachment_count' => count($attachmentsData),
            ]);

            // Send email synchronously (not queued)
            Mail::to($email)->send(new SendFeedback(
                $request->input('text'),
                $request->input('betreff'),
                ['attachments' => $attachmentsData],
                $user?->name,
                $user?->email,  // replyToEmail
                $user?->name    // replyToName
            ));

            Log::info('Email sent successfully to: ' . $email);

            return response()->json(['success' => 'Mail sent'], 200);
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'to' => $email,
            ]);

            return response()->json([
                'error' => 'Mail not sent',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
