<?php

namespace App\Services\App;

use Illuminate\Support\Facades\DB;

/**
 * Ungelesene Messenger-Nachrichten mit einer aggregierten Abfrage (B-60) –
 * statt alle Nachrichten aller Unterhaltungen zu laden.
 */
class MessengerUnread
{
    /** @return array<int,int> conversation_id => Anzahl */
    public static function perConversation(int $userId): array
    {
        return DB::table('messages as m')
            ->join('conversation_user as cu', function ($join) use ($userId) {
                $join->on('cu.conversation_id', '=', 'm.conversation_id')->where('cu.user_id', '=', $userId);
            })
            ->join('conversations as c', 'c.id', '=', 'm.conversation_id')
            ->where('c.is_active', true)
            ->whereNull('m.deleted_at')
            ->where('m.sender_id', '!=', $userId)
            ->where(fn ($q) => $q->whereNull('cu.joined_at')->orWhereColumn('m.created_at', '>=', 'cu.joined_at'))
            ->where(fn ($q) => $q->whereNull('cu.last_read_at')->orWhereColumn('m.created_at', '>', 'cu.last_read_at'))
            ->groupBy('m.conversation_id')
            ->pluck(DB::raw('count(*) as unread'), 'm.conversation_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    public static function total(int $userId): int
    {
        return array_sum(self::perConversation($userId));
    }
}
