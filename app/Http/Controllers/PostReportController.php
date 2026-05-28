<?php

namespace App\Http\Controllers;

use App\Model\Notification;
use App\Model\Post;
use App\Model\PostReport;
use App\Model\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\View\View;

class PostReportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            'password_expired',
        ];
    }

    /**
     * Beitrag melden (User-Aktion).
     */
    public function store(Request $request, Post $post): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        /** @var User $user */
        $user = auth()->user();

        // Eigene Beiträge nicht meldbar
        if ($post->author === $user->id) {
            return back()
                ->with('Meldung', 'Du kannst deine eigenen Beiträge nicht melden.')
                ->with('type', 'warning');
        }

        // Doppelte Meldung verhindern
        $alreadyReported = PostReport::where('post_id', $post->id)
            ->where('reporter_id', $user->id)
            ->whereNull('resolved_at')
            ->exists();

        if ($alreadyReported) {
            return back()
                ->with('Meldung', 'Du hast diesen Beitrag bereits gemeldet.')
                ->with('type', 'warning');
        }

        $report = PostReport::create([
            'post_id'     => $post->id,
            'reporter_id' => $user->id,
            'reason'      => $request->reason,
        ]);

        // Admins benachrichtigen (Nutzer mit 'edit settings' Berechtigung)
        $this->notifyAdmins($report, $post, $user);

        return back()
            ->with('Meldung', 'Beitrag wurde gemeldet. Ein Administrator wird ihn prüfen.')
            ->with('type', 'success');
    }

    /**
     * Admin: Übersicht aller gemeldeten Beiträge.
     */
    public function index(): View
    {
        $reports = PostReport::with(['post.autor', 'post.groups', 'reporter'])
            ->whereNull('resolved_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        $resolvedCount = PostReport::whereNotNull('resolved_at')->count();

        return view('nachrichten.admin.reports', compact('reports', 'resolvedCount'));
    }

    /**
     * Admin: Meldung als erledigt markieren.
     */
    public function resolve(PostReport $report): RedirectResponse
    {
        $report->update([
            'resolved_at' => now(),
            'resolved_by' => auth()->id(),
        ]);

        return back()
            ->with('Meldung', 'Meldung als erledigt markiert.')
            ->with('type', 'success');
    }

    /**
     * Admin: Gemeldeten Beitrag löschen und Meldung auflösen.
     */
    public function destroyPost(PostReport $report): RedirectResponse
    {
        $post = $report->post;

        // Alle offenen Meldungen für diesen Beitrag auflösen
        PostReport::where('post_id', $report->post_id)
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

        // Beitrag soft-deleten
        if ($post && ! $post->trashed()) {
            $post->delete();
        }

        return redirect()->route('post-reports.index')
            ->with('Meldung', 'Beitrag wurde gelöscht und alle zugehörigen Meldungen als erledigt markiert.')
            ->with('type', 'success');
    }

    /**
     * Benachrichtige alle Admins über eine neue Beitragsmeldung.
     */
    private function notifyAdmins(PostReport $report, Post $post, User $reporter): void
    {
        $admins = User::permission('edit settings')
            ->where('is_active', true)
            ->where('id', '!=', $reporter->id)
            ->get();

        if ($admins->isEmpty()) {
            return;
        }

        $title = 'Beitrag gemeldet: ' . mb_substr($post->header, 0, 50);
        $message = "{$reporter->name} hat den Beitrag \"{$post->header}\" gemeldet. Grund: " . mb_substr($report->reason, 0, 100);
        $url = route('post-reports.index');

        $notifications = [];
        foreach ($admins as $admin) {
            $notifications[] = [
                'user_id'    => $admin->id,
                'title'      => $title,
                'message'    => $message,
                'url'        => $url,
                'type'       => 'Beitragsmeldung',
                'icon'       => 'fas fa-flag',
                'read'       => false,
                'important'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($notifications);
    }
}


