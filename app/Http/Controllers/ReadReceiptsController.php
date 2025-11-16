<?php

namespace App\Http\Controllers;

use App\Mail\FinalReadReceiptReminderMail;
use App\Mail\RemindReadReceiptMail;
use App\Model\Post;
use App\Model\ReadReceipts;
use App\Notifications\ReadReceiptReminderNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class ReadReceiptsController extends Controller
{

    public function store(Request $request)
    {
        ReadReceipts::firstOrCreate([
            'post_id' => $request->post_id,
            'user_id' => auth()->id(),
        ]);
        return redirect()->back()->with([
            'type' => 'success',
            'Meldung' => 'Lesebestätigung erfolgreich gespeichert.',
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

            if (!$deadline) {
                continue;
            }

            // Prüfe ob wir im 3-Tage-Fenster vor der Deadline sind
            $threeDaysBefore = $deadline->copy()->subDays(3);
            $now = now();

            if ($now->lt($threeDaysBefore) || $now->gt($deadline)) {
                continue;
            }

            $users = $post->users;
            $receipts = $post->receipts;

            foreach ($users as $user) {
                // Prüfe ob User bereits bestätigt hat
                $existingReceipt = $receipts->where('user_id', $user->id)->first();

                if (!$existingReceipt) {
                    // Erstelle einen Eintrag, um zu tracken, dass wir erinnert haben
                    ReadReceipts::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'reminded_at' => now(),
                    ]);

                    // Versende E-Mail
                    $mail = new RemindReadReceiptMail(
                        $user->email,
                        $user->name,
                        $post->header,
                        $deadline->format('d.m.Y'),
                        $post->id
                    );
                    $mail->subject('Lesebestätigung fehlt: ' . $post->header);

                    try {
                        Mail::to($user->email)->queue($mail);
                    } catch (\Exception $e) {
                        Log::error('Mail konnte nicht versendet werden: ' . $e->getMessage());
                    }

                    // Versende In-App-Benachrichtigung
                    try {
                        $user->notify(new ReadReceiptReminderNotification($post, $deadline->format('d.m.Y')));
                    } catch (\Exception $e) {
                        Log::error('In-App-Benachrichtigung konnte nicht versendet werden: ' . $e->getMessage());
                    }
                } elseif ($existingReceipt && $existingReceipt->reminded_at && !$existingReceipt->created_at->eq($existingReceipt->updated_at)) {
                    // User hat noch nicht bestätigt, aber wir haben schon erinnert
                    // Nichts tun in dieser Phase
                }
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

            if (!$deadline) {
                continue;
            }

            // Prüfe ob die Deadline abgelaufen ist (mit 1 Stunde Toleranz)
            $now = now();
            if ($now->lt($deadline) || $now->gt($deadline->copy()->addHours(1))) {
                continue;
            }

            $users = $post->users;
            $receipts = $post->receipts;

            foreach ($users as $user) {
                $existingReceipt = $receipts->where('user_id', $user->id)->first();

                // Nur wenn User erinnert wurde aber noch nicht bestätigt hat
                if ($existingReceipt &&
                    $existingReceipt->reminded_at &&
                    !$existingReceipt->final_reminder_sent_at &&
                    $existingReceipt->created_at->eq($existingReceipt->updated_at)) {

                    // Hole die E-Mail-Adresse des Autors
                    $authorEmail = $post->autor->email ?? config('mail.from.address');

                    // Versende finale E-Mail mit Nachrichteninhalt und Lesebestätigung
                    $mail = new FinalReadReceiptReminderMail(
                        $user->email,
                        $user->name,
                        $post->header,
                        strip_tags($post->news), // Nachrichteninhalt
                        $deadline->format('d.m.Y'),
                        $post->id,
                        $authorEmail // E-Mail des Autors für Lesebestätigung
                    );

                    try {
                        Mail::to($user->email)->queue($mail);

                        // Markiere finale Erinnerung als versendet
                        $existingReceipt->update([
                            'final_reminder_sent_at' => now(),
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Finale Erinnerungs-Mail konnte nicht versendet werden: ' . $e->getMessage());
                    }
                }
            }
        }
    }
}


