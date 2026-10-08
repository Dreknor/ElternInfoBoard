<?php

namespace App\Http\Controllers;

use App\Model\MessageReport;
use App\Model\PostReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gemeinsame Anlaufstelle für die Moderation gemeldeter Beiträge und Messenger-Nachrichten.
 *
 * Beiträge: Berechtigung 'edit settings' · Nachrichten: Berechtigung 'moderate messages'
 */
class ModerationController extends Controller
{
    public const TAB_POSTS = 'beitraege';

    public const TAB_MESSAGES = 'nachrichten';

    public function index(Request $request): View
    {
        $user = $request->user();

        $tabs = [];

        if ($user->can('edit settings')) {
            $tabs[self::TAB_POSTS] = [
                'label' => 'Beiträge',
                'icon'  => 'fas fa-newspaper',
                'open'  => PostReport::whereNull('resolved_at')->count(),
            ];
        }

        if ($user->can('moderate messages')) {
            $tabs[self::TAB_MESSAGES] = [
                'label' => 'Nachrichten',
                'icon'  => 'fas fa-comments',
                'open'  => MessageReport::whereNull('resolved_at')->count(),
            ];
        }

        abort_if($tabs === [], 403);

        $active = $request->query('tab');
        if (! array_key_exists($active, $tabs)) {
            // Standard: erster Tab mit offenen Meldungen, sonst der erste erlaubte
            $active = collect($tabs)->filter(fn ($tab) => $tab['open'] > 0)->keys()->first()
                ?? array_key_first($tabs);
        }

        if ($active === self::TAB_POSTS) {
            $reports = PostReport::with(['post.autor', 'post.groups', 'reporter'])
                ->whereNull('resolved_at')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString();

            $resolvedCount = PostReport::whereNotNull('resolved_at')->count();
        } else {
            $reports = MessageReport::with(['message.sender', 'message.conversation', 'reporter'])
                ->whereNull('resolved_at')
                ->orderByDesc('created_at')
                ->paginate(20)
                ->withQueryString();

            $resolvedCount = MessageReport::whereNotNull('resolved_at')->count();
        }

        return view('moderation.index', compact('tabs', 'active', 'reports', 'resolvedCount'));
    }
}
