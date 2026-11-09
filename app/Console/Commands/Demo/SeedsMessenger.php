<?php

namespace App\Console\Commands\Demo;

use App\Model\Conversation;
use App\Model\Message;
use App\Model\MessageReport;
use App\Model\PostReport;
use App\Model\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Demo-Daten (demo:setup). Wird in App\Console\Commands\DemoSetup eingebunden und
 * nutzt dessen Zustand: $this->users, $this->groups (IDs), $this->children.
 *
 * Messenger: Konversationen und Nachrichten werden direkt über die Modelle angelegt
 * (nicht über die Controller), daher entstehen keine Benachrichtigungen/Push.
 */
trait SeedsMessenger
{
    private function createMessenger(User $admin): void
    {
        $u = $this->users;

        // Lehrkräfte haben über die Rolle „Mitarbeiter“ kein 'use messenger'.
        // Für die Demo-Direktchats Eltern ↔ Lehrkraft direkt am Benutzer vergeben.
        foreach (['lehrerin', 'lehrer2'] as $key) {
            if (isset($u[$key]) && ! $u[$key]->hasDirectPermission('use messenger')) {
                $u[$key]->givePermissionTo('use messenger');
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // ── Gruppenchat Klasse 1a ─────────────────────────────────
        $chat1a = $this->demoGroupConversation('klasse_1a', $admin, now()->subDays(21));
        if ($chat1a && ! $chat1a->messages()->withTrashed()->exists()) {
            $d = now()->startOfDay();
            $m1 = $this->demoMessage($chat1a, $u['lehrer2'], 'Liebe Eltern der 1a, hier können Sie sich untereinander austauschen. Bitte denken Sie an einen freundlichen Umgangston. Für Fragen an mich nutzen Sie gern auch eine Direktnachricht.', $d->copy()->subDays(4)->setTime(16, 5));
            $this->demoMessage($chat1a, $u['mueller'], 'Danke, Herr Berger! Eine Frage an alle: Weiß jemand, ob die Kinder für den Wandertag am Freitag Gummistiefel brauchen?', $d->copy()->subDays(4)->setTime(19, 42));
            $this->demoMessage($chat1a, $u['bauer'], 'Laut Elternbrief festes Schuhwerk – bei dem Wetter würde ich aber Gummistiefel einpacken.', $d->copy()->subDays(4)->setTime(20, 3));
            $this->demoMessage($chat1a, $u['lehrer2'], 'Gummistiefel sind eine gute Idee. Bitte auch Regenjacke und ein Getränk mitgeben.', $d->copy()->subDays(3)->setTime(7, 31), $m1);
            $this->demoMessage($chat1a, $u['bauer'], 'Falls jemand für die Fahrgemeinschaft fragen möchte: Die Handynummer von Frau Weiß aus der 1b ist 0176 00000000.', $d->copy()->subDays(3)->setTime(18, 15));
            $this->demoMessage($chat1a, $u['mueller'], 'Hat jemand zufällig eine blaue Brotdose mit Dinosauriern eingesteckt? Lena vermisst ihre seit gestern.', $d->copy()->subDays(2)->setTime(13, 20));
            $this->demoMessage($chat1a, $u['bauer'], 'Manche Eltern hier sollten sich echt mal an die eigene Nase fassen, statt ständig über die Hausaufgaben zu meckern.', $d->copy()->subDays(1)->setTime(21, 47));
            $this->demoMessage($chat1a, $u['lehrer2'], 'Erinnerung: Morgen ist Fototermin. Bitte die Kinder bis 8:00 Uhr in die Schule bringen.', now()->subHours(3));
            $this->demoMessage($chat1a, $u['bauer'], 'Die Brotdose lag bei uns im Ranzen – Tim bringt sie morgen mit!', now()->subHour());

            // Alle haben gelesen – außer Familie Müller (letzte drei Nachrichten ungelesen)
            DB::table('conversation_user')->where('conversation_id', $chat1a->id)->update(['last_read_at' => now()]);
            $this->demoMarkRead($chat1a, $u['mueller'], $d->copy()->subDays(1)->setTime(21, 0));
            $this->demoMarkRead($chat1a, $u['lehrer2'], now()->subHours(3));
        }

        // ── Gruppenchat Elternrat ─────────────────────────────────
        $chatEr = $this->demoGroupConversation('elternrat_gruppe', $admin, now()->subDays(30));
        if ($chatEr && ! $chatEr->messages()->withTrashed()->exists()) {
            $d = now()->startOfDay();
            $this->demoMessage($chatEr, $u['elternrat'], 'Hallo zusammen, die nächste Elternratssitzung ist am Donnerstag um 19:30 Uhr im Raum 104. Themen bitte bis Mittwoch hier sammeln.', $d->copy()->subDays(5)->setTime(9, 15));
            $this->demoMessage($chatEr, $u['fischer'], 'Ich würde gern über die Ganztagsangebote im zweiten Halbjahr sprechen.', $d->copy()->subDays(5)->setTime(12, 40));
            $this->demoMessage($chatEr, $u['mueller'], 'Von mir: Stand Schulhofgestaltung und die Helferliste fürs Herbstfest.', $d->copy()->subDays(4)->setTime(18, 5));
            $this->demoMessage($chatEr, $u['elternrat'], 'Danke! Ich nehme beides auf. Kann jemand das Protokoll übernehmen?', $d->copy()->subDays(4)->setTime(20, 30));
            $this->demoMessage($chatEr, $u['fischer'], 'Ich übernehme das Protokoll.', $d->copy()->subDays(3)->setTime(7, 55));
            $this->demoMessage($chatEr, $u['elternrat'], 'Die Tagesordnung ist fertig und hängt im Elternrat-Bereich. Bis Donnerstag!', $d->copy()->subDays(1)->setTime(17, 20));

            DB::table('conversation_user')->where('conversation_id', $chatEr->id)->update(['last_read_at' => now()]);
            $this->demoMarkRead($chatEr, $u['fischer'], $d->copy()->subDays(3)->setTime(8, 0));
        }

        // ── Direktchat Familie Müller ↔ Herr Berger (Klassenlehrer 1a) ──
        $dm1 = $this->demoDirectConversation($u['mueller'], $u['lehrer2'], now()->subDays(6));
        if ($dm1 && ! $dm1->messages()->withTrashed()->exists()) {
            $d = now()->startOfDay();
            $this->demoMessage($dm1, $u['mueller'], 'Guten Tag Herr Berger, Lena hat erzählt, dass sie im Lesen gerade etwas Mühe hat. Können wir zu Hause etwas unterstützen?', $d->copy()->subDays(2)->setTime(19, 10));
            $this->demoMessage($dm1, $u['lehrer2'], 'Guten Abend Frau Müller, danke für Ihre Nachricht. Lena macht gute Fortschritte. Zehn Minuten gemeinsames Lesen am Tag helfen sehr – ich gebe ihr ein Leseheft mit.', $d->copy()->subDays(1)->setTime(15, 25));
            $this->demoMessage($dm1, $u['lehrer2'], 'Gern können wir uns auch beim Elterngespräch in zwei Wochen dazu austauschen.', $d->copy()->subDays(1)->setTime(15, 27));

            // Antwort der Lehrkraft für Familie Müller noch ungelesen
            $this->demoMarkRead($dm1, $u['mueller'], $d->copy()->subDays(2)->setTime(19, 10));
            $this->demoMarkRead($dm1, $u['lehrer2'], now());
        }

        // ── Direktchat Familie Müller ↔ Familie Bauer ────────────
        $dm2 = $this->demoDirectConversation($u['mueller'], $u['bauer'], now()->subDays(10));
        if ($dm2 && ! $dm2->messages()->withTrashed()->exists()) {
            $d = now()->startOfDay();
            $this->demoMessage($dm2, $u['bauer'], 'Hallo! Sollen wir zum Elternabend zusammen fahren? Wir hätten noch zwei Plätze frei.', $d->copy()->subDays(3)->setTime(10, 0));
            $this->demoMessage($dm2, $u['mueller'], 'Sehr gern, danke! Holt ihr uns um 18:45 Uhr ab?', $d->copy()->subDays(3)->setTime(11, 30));
            $this->demoMessage($dm2, $u['bauer'], 'Machen wir. Bis dann!', $d->copy()->subDays(3)->setTime(11, 42));

            $this->demoMarkRead($dm2, $u['mueller'], now());
            $this->demoMarkRead($dm2, $u['bauer'], now());
        }

        // ── Direktchat Familie Koch ↔ Frau Hoffmann (Klassenlehrerin 3b) ──
        $dm3 = $this->demoDirectConversation($u['koch'], $u['lehrerin'], now()->subDays(8));
        if ($dm3 && ! $dm3->messages()->withTrashed()->exists()) {
            $d = now()->startOfDay();
            $this->demoMessage($dm3, $u['koch'], 'Guten Tag Frau Hoffmann, muss Paul für den Ausflug ins Elbsandsteingebirge ein Lunchpaket mitbringen?', $d->copy()->subDays(4)->setTime(8, 20));
            $this->demoMessage($dm3, $u['lehrerin'], 'Guten Tag Familie Koch, ja, bitte ein Lunchpaket und ausreichend zu trinken. Mittags machen wir ein Picknick.', $d->copy()->subDays(4)->setTime(13, 5));
            $this->demoMessage($dm3, $u['koch'], 'Vielen Dank für die schnelle Antwort!', $d->copy()->subDays(4)->setTime(13, 40));

            $this->demoMarkRead($dm3, $u['koch'], now());
            $this->demoMarkRead($dm3, $u['lehrerin'], now());
        }
    }

    private function createModeration(User $admin): void
    {
        $u = $this->users;
        $resolver = $u['schulleitung'] ?? $admin;

        // ── Gemeldete Beiträge ────────────────────────────────────
        $postId = fn (string $like) => DB::table('posts')->where('header', 'like', $like)->value('id');

        if ($id = $postId('Musikschule Radebeul%')) {
            $this->demoReport(PostReport::class, 'post_id', $id, $u['schulz'],
                'Das ist Werbung für ein kostenpflichtiges Angebot. Gehört so etwas ins Elternportal?',
                now()->subDays(2)->setTime(20, 14));
        }
        if ($id = $postId('Spendenaktion%')) {
            $this->demoReport(PostReport::class, 'post_id', $id, $u['koch'],
                'Der Spendenaufruf setzt Familien mit wenig Geld unter Druck.',
                now()->subDays(6)->setTime(9, 3),
                now()->subDays(5)->setTime(10, 30), $resolver);
        }

        // ── Gemeldete Nachrichten ─────────────────────────────────
        $message = fn (string $like) => Message::withTrashed()->where('body', 'like', $like)->first();

        if ($m = $message('Manche Eltern hier sollten%')) {
            $this->demoReport(MessageReport::class, 'message_id', $m->id, $u['mueller'],
                'Unfreundlicher Ton gegenüber anderen Eltern.',
                $m->created_at->copy()->addMinutes(38));
        }
        if ($m = $message('Falls jemand für die Fahrgemeinschaft%')) {
            $report = $this->demoReport(MessageReport::class, 'message_id', $m->id, $u['lehrer2'],
                'Hier wird die private Telefonnummer einer anderen Familie ohne deren Zustimmung weitergegeben.',
                $m->created_at->copy()->addHours(1),
                $m->created_at->copy()->addHours(14), $resolver);

            // Nachricht wurde von der Moderation entfernt (Soft-Delete)
            if ($report->resolved_at && ! $m->trashed()) {
                $m->deleted_at = $report->resolved_at;
                $m->save();
            }
        }
    }

    // ── Hilfsmethoden ─────────────────────────────────────────────

    /**
     * Gruppen-Chat wie GroupsController::toggleChat aktivieren: has_chat setzen,
     * Konversation anlegen/reaktivieren, alle Gruppenmitglieder aufnehmen.
     */
    private function demoGroupConversation(string $groupKey, User $admin, Carbon $createdAt): ?Conversation
    {
        $groupId = $this->groups[$groupKey] ?? null;
        if (! $groupId) {
            return null;
        }
        $group = DB::table('groups')->where('id', $groupId)->first();
        DB::table('groups')->where('id', $groupId)->update(['has_chat' => true, 'updated_at' => now()]);

        $conv = Conversation::withoutGlobalScopes()
            ->where('group_id', $groupId)
            ->where('type', 'group')
            ->first();

        if ($conv) {
            if ($conv->trashed()) {
                $conv->restore();
            }
            $conv->update(['is_active' => true]);
        } else {
            $conv = new Conversation([
                'type'       => 'group',
                'group_id'   => $groupId,
                'title'      => $group->name,
                'created_by' => $admin->id,
                'is_active'  => true,
            ]);
            $conv->created_at = $createdAt;
            $conv->updated_at = $createdAt;
            $conv->save();
        }

        $memberIds = DB::table('group_user')->where('group_id', $groupId)->pluck('user_id')
            ->map(fn ($id) => (int) $id)->all();
        $existing = $conv->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        // Neue Konversation: Mitglieder von Anfang an dabei. Bestehende (erneuter Lauf):
        // später hinzugekommene Mitglieder sehen wie im echten Ablauf nur neue Nachrichten.
        $joinedAt = $conv->wasRecentlyCreated ? $createdAt : now();
        $attach = collect(array_diff($memberIds, $existing))
            ->mapWithKeys(fn ($id) => [$id => ['joined_at' => $joinedAt]])
            ->all();
        if ($attach) {
            $conv->users()->syncWithoutDetaching($attach);
        }

        return $conv;
    }

    /** Direktchat zweier Personen suchen oder anlegen (wie MessengerController::startDirect). */
    private function demoDirectConversation(?User $a, ?User $b, Carbon $createdAt): ?Conversation
    {
        if (! $a || ! $b) {
            return null;
        }

        $conv = Conversation::where('type', 'direct')
            ->forUser($a->id)
            ->whereHas('users', fn ($q) => $q->where('users.id', $b->id))
            ->first();

        if (! $conv) {
            $conv = new Conversation([
                'type'       => 'direct',
                'created_by' => $a->id,
                'is_active'  => true,
            ]);
            $conv->created_at = $createdAt;
            $conv->updated_at = $createdAt;
            $conv->save();
            $conv->users()->syncWithoutDetaching([
                $a->id => ['joined_at' => $createdAt],
                $b->id => ['joined_at' => $createdAt],
            ]);
        }

        return $conv;
    }

    private function demoMessage(Conversation $conv, User $sender, string $body, Carbon $at, ?Message $replyTo = null): Message
    {
        $message = new Message([
            'conversation_id' => $conv->id,
            'sender_id'       => $sender->id,
            'body'            => $body,
            'type'            => 'text',
            'reply_to_id'     => $replyTo?->id,
        ]);
        $message->created_at = $at;
        $message->updated_at = $at;
        $message->save();

        return $message;
    }

    private function demoMarkRead(Conversation $conv, ?User $user, Carbon $at): void
    {
        if ($user) {
            $conv->users()->updateExistingPivot($user->id, ['last_read_at' => $at]);
        }
    }

    /**
     * Meldung (PostReport/MessageReport) idempotent anlegen.
     *
     * @param  class-string<PostReport|MessageReport>  $class
     */
    private function demoReport(string $class, string $fk, int $id, User $reporter, string $reason, Carbon $at, ?Carbon $resolvedAt = null, ?User $resolver = null): PostReport|MessageReport
    {
        $report = $class::where($fk, $id)->where('reporter_id', $reporter->id)->first();
        if ($report) {
            return $report;
        }

        $report = new $class([
            $fk           => $id,
            'reporter_id' => $reporter->id,
            'reason'      => $reason,
            'resolved_at' => $resolvedAt,
            'resolved_by' => $resolvedAt ? $resolver?->id : null,
        ]);
        $report->created_at = $at;
        $report->updated_at = $resolvedAt ?? $at;
        $report->save();

        return $report;
    }
}
