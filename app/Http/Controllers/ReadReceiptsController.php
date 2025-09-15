<?php

namespace App\Http\Controllers;

use App\Mail\ErinnerungRuecklaufFehlt;
use App\Mail\RemindReadReceiptMail;
use App\Model\Post;
use App\Model\ReadReceipts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use function PHPUnit\Framework\isFalse;

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

    public function remind()
    {

        $posts = Post::query()
            ->where('read_receipt', '1')
            ->where('archiv_ab', '<', now()->addDays(3))
            ->where('archiv_ab', '>', now())
            ->where('released', '1')
            ->with('users')
            ->with('receipts')
            ->get();

        foreach ($posts as $post) {
            $users = $post->users;
            $receipts = $post->receipts;

            foreach ($users as $user) {
                if ($receipts->where('user_id', $user->id)->isEmpty()) {
                    $mail = new RemindReadReceiptMail($user->email, $user->name, $post->header, $post->archiv_ab->format('d.m.Y'), $post->id);
                    $mail->subject('Lesebestätigung fehlt: ' . $post->thema);
                    try {
                        Mail::to($user->email)->queue($mail);

                    } catch (\Exception $e) {
                        Log::error('Mail konnte nicht versendet werden: ' . $e->getMessage());
                    }
                }
            }
        }

    }
}
