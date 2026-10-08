<?php

namespace App\Http\Controllers;

use App\Mail\FinalReadReceiptReminderMail;
use App\Mail\RemindReadReceiptMail;
use App\Model\Notification;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Services\Notifications\NotificationCategory;
use App\Services\Notifications\NotificationPreferences;
use App\Services\ReadReceiptStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ReadReceiptsController extends Controller
{
    public function store(Request $request)
    {
        $receipt = ReadReceipts::firstOrCreate(
            [
                'post_id' => $request->post_id,
                'user_id' => auth()->id(),
            ],
            [
                'confirmed_at' => now(),
            ]
        );

        // Falls der Eintrag bereits existierte, aber noch nicht bestätigt war
        if ($receipt->wasRecentlyCreated === false && is_null($receipt->confirmed_at)) {
            $receipt->confirmed_at = now();
            $receipt->save();
        }

        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Lesebestätigung erfolgreich gespeichert.',
        ]);
    }

    /**
     * Admin: Bestätigt Lesebestätigung für einen bestimmten Nutzer
     * (z.B. wenn E-Mail-Lesebestätigung eingegangen ist)
     */
    public function confirmForUser(Request $request, Post $post, \App\Model\User $user)
    {
        $receipt = ReadReceipts::firstOrCreate(
            [
                'post_id' => $post->id,
                'user_id' => $user->id,
            ],
            [
                'confirmed_at' => now(),
            ]
        );

        // Falls der Eintrag bereits existierte, aber noch nicht bestätigt war
        if ($receipt->wasRecentlyCreated === false && is_null($receipt->confirmed_at)) {
            $receipt->confirmed_at = now();
            $receipt->save();
        }

        Log::info($request->user()->name.' hat Lesebestätigung für Nutzer '.$user->name.' und Nachricht "'.$post->header.'" bestätigt.');

        return response()->json([
            'success' => true,
            'message' => 'Lesebestätigung für '.$user->name.' wurde gespeichert.',
            'user_id' => $user->id,
            'confirmed_at' => $receipt->confirmed_at->format('d.m.Y H:i'),
        ]);
    }

    /**
     * Erste Erinnerung: 3 Tage vor Ablauf
     * Versendet E-Mail und In-App-Benachrichtigung
     */
    public function remind()
    {
        $posts = Post::query()
            ->where('read_receipt', '1')
            ->where('released', '1')
            ->with('users')
            ->with('receipts')
            ->get();

        foreach ($posts as $post) {
            // Bestimme die Deadline (entweder read_receipt_deadline oder archiv_ab)
            $deadline = $post->read_receipt_deadline ?? $post->archiv_ab;

            if (! $deadline) {
                continue;
            }

            // Prüfe ob wir im 3-Tage-Fenster vor der Deadline sind
            $threeDaysBefore = $deadline->copy()->subDays(3);
            $now = now();

            if ($now->lt($threeDaysBefore) || $now->gt($deadline)) {
                continue;
            }

            $users = $post->users->unique('id');
            $receipts = $post->receipts;

            foreach ($users as $user) {
                $existingReceipt = $receipts->where('user_id', $user->id)->first();

                // Überspringe bereits bestätigte Nutzer
                if ($existingReceipt && $existingReceipt->confirmed_at) {
                    continue;
                }

                // Überspringe, wenn bereits erledigt (Familie, Person oder je Kind)
                if (app(ReadReceiptStatusService::class)->isSatisfied($user, $post, $receipts->whereNotNull('confirmed_at')->keyBy('user_id'))) {
                    continue;
                }

                // Überspringe bereits erinnerte Nutzer – Erinnerung wird nur einmal versendet
                if ($existingReceipt && $existingReceipt->reminded_at) {
                    continue;
                }

                if (! $existingReceipt) {
                    // Erstelle einen Eintrag ausschließlich zum Tracken der Erinnerung
                    ReadReceipts::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'reminded_at' => now(),
                    ]);
                } else {
                    $existingReceipt->update(['reminded_at' => now()]);
                }

                // Versende E-Mail (sofern der Nutzer E-Mails für Erinnerungen zulässt)
                if ($user->email && NotificationPreferences::allows($user, NotificationCategory::ERINNERUNGEN, 'mail')) {
                    $mail = new RemindReadReceiptMail(
                        $user->email,
                        $user->name,
                        $post->header,
                        $deadline->format('d.m.Y'),
                        $post->id
                    );
                    $mail->subject('Lesebestätigung fehlt: '.$post->header);

                    try {
                        Mail::to($user->email)->queue($mail);
                    } catch (\Exception $e) {
                        Log::error('Mail konnte nicht versendet werden: '.$e->getMessage());
                    }
                }

                // Glocke + App-/Browser-Push (über das created-Event des Modells)
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'Lesebestätigung',
                    'title' => 'Lesebestätigung fehlt',
                    'message' => 'Bitte bestätigen Sie die Nachricht „'.$post->header.'“ bis zum '.$deadline->format('d.m.Y').'.',
                    'url' => url('post/'.$post->id),
                ]);
            }
        }
    }

    /**
     * Finale Erinnerung: Bei Ablauf ohne Bestätigung
     * Versendet Nachrichteninhalt erneut mit E-Mail-Lesebestätigung
     */
    public function sendFinalReminder()
    {
        $posts = Post::query()
            ->where('read_receipt', '1')
            ->where('released', '1')
            ->with('users')
            ->with('receipts')
            ->get();

        foreach ($posts as $post) {
            // Bestimme die Deadline
            $deadline = $post->read_receipt_deadline ?? $post->archiv_ab;

            if (! $deadline) {
                continue;
            }

            // Prüfe ob die Deadline abgelaufen ist (mit 1 Stunde Toleranz)
            $now = now();
            if ($now->lt($deadline) || $now->gt($deadline->copy()->addHours(1))) {
                continue;
            }

            $users = $post->users->unique('id');
            $receipts = $post->receipts;

            foreach ($users as $user) {
                $existingReceipt = $receipts->where('user_id', $user->id)->first();

                // Nur wenn Nutzer nicht bestätigt hat (confirmed_at null) und bereits erinnert wurde
                if ($existingReceipt && is_null($existingReceipt->confirmed_at) && $existingReceipt->reminded_at && ! $existingReceipt->final_reminder_sent_at) {

                    // Überspringe, wenn bereits erledigt (Familie, Person oder je Kind)
                    if (app(ReadReceiptStatusService::class)->isSatisfied($user, $post, $receipts->whereNotNull('confirmed_at')->keyBy('user_id'))) {
                        continue;
                    }

                    // Hole die E-Mail-Adresse des Autors
                    $authorEmail = $post->autor?->email ?? config('mail.from.address');

                    // Versende finale E-Mail mit Nachrichteninhalt und Lesebestätigung
                    $mail = new FinalReadReceiptReminderMail(
                        $user->email,
                        $user->name,
                        $post->header,
                        strip_tags($post->news), // Nachrichteninhalt
                        $deadline->format('d.m.Y'),
                        $post->id,
                        $authorEmail
                    );

                    try {
                        Mail::to($user->email)->queue($mail);

                        // Markiere finale Erinnerung als versendet
                        $existingReceipt->update([
                            'final_reminder_sent_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Finale Erinnerungs-Mail konnte nicht versendet werden: '.$e->getMessage());
                    }
                }
            }
        }
    }
}
