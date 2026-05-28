<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Datenschutz-Auskunft – {{ $user->name }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a2e;
            margin: 0;
            padding: 20px 30px;
            line-height: 1.5;
        }

        /* ---- Seitenkopf ---- */
        .page-header {
            border-bottom: 3px solid #1d4ed8;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .page-header h1 {
            font-size: 18px;
            color: #1d4ed8;
            margin: 0 0 2px 0;
        }
        .page-header .meta {
            font-size: 9px;
            color: #64748b;
        }

        /* ---- Info-Box ---- */
        .info-box {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-size: 9px;
            color: #1e3a8a;
        }

        /* ---- Sektions-Header ---- */
        .section-header {
            background-color: #1d4ed8;
            color: white;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 10px;
            margin-top: 14px;
            margin-bottom: 0;
        }

        /* ---- Tabellen ---- */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        table thead tr {
            background-color: #f1f5f9;
        }
        table thead th {
            text-align: left;
            padding: 4px 8px;
            border-bottom: 2px solid #cbd5e1;
            font-weight: bold;
            color: #374151;
        }
        table tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }
        table tbody td, table tbody th {
            padding: 4px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }
        table tbody th {
            font-weight: 600;
            width: 35%;
            color: #374151;
        }

        /* ---- Badges ---- */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-green  { background: #dcfce7; color: #166534; }
        .badge-red    { background: #fee2e2; color: #991b1b; }
        .badge-amber  { background: #fef3c7; color: #92400e; }

        /* ---- Leer-Meldung ---- */
        .empty { color: #94a3b8; font-style: italic; padding: 6px 8px; }

        /* ---- Seitenfuß ---- */
        .page-footer {
            position: fixed;
            bottom: 10px;
            left: 30px;
            right: 30px;
            font-size: 8px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
            text-align: center;
        }

        /* ---- Tag-Chips (Gruppen etc.) ---- */
        .chip-list { padding: 6px 8px; }
        .chip {
            display: inline-block;
            background: #ede9fe;
            color: #5b21b6;
            border-radius: 10px;
            padding: 1px 7px;
            margin: 2px;
            font-size: 8px;
        }
        .chip-amber { background: #fef3c7; color: #92400e; }
        .chip-slate { background: #f1f5f9; color: #334155; }
    </style>
</head>
<body>

<div class="page-footer">
    Datenschutz-Auskunft nach Art. 15 DSGVO &ndash; {{ config('app.name') }} &ndash;
    Exportiert am {{ $exportAt->format('d.m.Y H:i') }} Uhr &ndash; {{ $user->name }}
</div>

{{-- Seitenkopf --}}
<div class="page-header">
    <h1>Datenschutz-Auskunft &ndash; Gespeicherte Daten</h1>
    <div class="meta">
        Auskunft nach Art. 15 DSGVO &bull; Evangelisches Schulzentrum Radebeul &bull;
        Exportiert am {{ $exportAt->format('d.m.Y H:i') }} Uhr &bull;
        Konto: {{ $user->name }} ({{ $user->email }})
    </div>
</div>

{{-- Info-Box --}}
<div class="info-box">
    Diese Datei enthält alle in <strong>{{ config('app.name') }}</strong> gespeicherten personenbezogenen Daten für das o.g. Konto.
    IP-Adressen werden von dieser Anwendung nicht gespeichert. Schulintern darüber hinausgehende Daten sind hier nicht erfasst.
</div>

{{-- ===== BENUTZERKONTO ===== --}}
<div class="section-header">Benutzerkonto-Daten</div>
@php
    $cookieStatus  = $user->remember_token != '' ? 'Token vorhanden (verschlüsselt)' : 'Kein Cookie gespeichert';
    $cookieClass   = $user->remember_token != '' ? 'badge-amber' : 'badge-green';
    $benachricht   = $user->benachrichtigung == 'weekly' ? 'Wöchentlich' : 'Täglich';
    $benachricht  .= $user->sendCopy ? ' · Kopie eigener Mails gewünscht' : '';
    if ($user->track_login && $user->last_online_at) {
        $letzterLogin = $user->last_online_at->format('d.m.Y H:i:s') . ' Uhr';
    } elseif ($user->track_login) {
        $letzterLogin = 'Noch nicht aufgezeichnet';
    } else {
        $letzterLogin = '– (Kein Login-Tracking gewünscht)';
    }
@endphp
<table>
    <tbody>
        <tr><th>Name</th><td>{{ $user->name }}</td></tr>
        <tr><th>E-Mail</th><td>{{ $user->email }}</td></tr>
        <tr><th>Öffentliche E-Mail</th><td>{{ $user->publicMail ?: '–' }}</td></tr>
        <tr><th>Öffentliche Telefonnummer</th><td>{{ $user->publicPhone ?: '–' }}</td></tr>
        <tr><th>UUID</th><td>{{ $user->uuid }}</td></tr>
        <tr>
            <th>Konto aktiv</th>
            <td>
                @if($user->is_active)
                    <span class="badge badge-green">Aktiv</span>
                @else
                    <span class="badge badge-red">Deaktiviert</span>
                    @if($user->deactivated_at)
                        seit {{ $user->deactivated_at->format('d.m.Y H:i') }} Uhr
                    @endif
                @endif
            </td>
        </tr>
        <tr><th>Konto erstellt</th><td>{{ $user->created_at->format('d.m.Y H:i:s') }} Uhr</td></tr>
        <tr><th>Konto geändert</th><td>{{ $user->updated_at->format('d.m.Y H:i:s') }} Uhr</td></tr>
        <tr><th>Kennwort</th><td><em>Nur als bcrypt-Hash gespeichert – nicht entschlüsselbar</em></td></tr>
        <tr>
            <th>„Angemeldet bleiben"-Cookie</th>
            <td><span class="badge {{ $cookieClass }}">{{ $cookieStatus }}</span></td>
        </tr>
        <tr><th>Letzter Login</th><td>{{ $letzterLogin }}</td></tr>
        <tr><th>E-Mail-Benachrichtigungen</th><td>{{ $benachricht }}</td></tr>
        <tr><th>Letzte Info-E-Mail</th><td>{{ $user->lastEmail?->format('d.m.Y H:i:s') ?? '–' }}</td></tr>
        @if($user->releaseCalendar)
            <tr>
                <th>Kalender-Freigabe</th>
                <td>Aktiviert{{ $user->calendar_prefix ? ' · Prefix: ' . $user->calendar_prefix : '' }}</td>
            </tr>
        @endif
        @if($user->sorg2)
            <tr><th>Sorgeberechtigter 2</th><td>{{ $user->sorgeberechtigter2?->name }}</td></tr>
        @endif
        <tr><th>In Messenger-Suche sichtbar</th><td>{{ $user->messenger_discoverable ? 'Ja' : 'Nein' }}</td></tr>
    </tbody>
</table>

{{-- ===== GRUPPEN ===== --}}
<div class="section-header" style="background:#7c3aed;">Zugeordnete Gruppen</div>
<div class="chip-list">
    @forelse($user->groups()->withoutGlobalScopes()->get() as $group)
        <span class="chip">{{ $group->name }}</span>
    @empty
        <span class="empty">Keine Gruppen zugeordnet.</span>
    @endforelse
</div>

{{-- ===== ROLLEN ===== --}}
<div class="section-header" style="background:#475569;">Rollen &amp; Berechtigungen</div>
@php $directPermissions = $user->getDirectPermissions(); @endphp
<div class="chip-list">
    @if($user->roles->count())
        <div style="font-size:8px;font-weight:bold;color:#475569;margin-bottom:3px;">Rollen:</div>
        @foreach($user->roles as $role)
            <span class="chip chip-slate">{{ $role->name }}</span>
        @endforeach
    @endif
    @if($directPermissions->count())
        <div style="font-size:8px;font-weight:bold;color:#475569;margin-top:5px;margin-bottom:3px;">Direkte Berechtigungen:</div>
        @foreach($directPermissions as $perm)
            <span class="chip chip-amber">{{ $perm->name }}</span>
        @endforeach
    @endif
    @if(!$user->roles->count() && !$directPermissions->count())
        <span class="empty">Keine Rollen oder Berechtigungen.</span>
    @endif
</div>

{{-- ===== KINDER ===== --}}
@if($user->children_rel->count())
    <div class="section-header" style="background:#be185d;">Verknüpfte Kinder / Schutzbefohlene</div>
    <table>
        <thead><tr><th>Name</th><th>Gruppe / Klasse</th></tr></thead>
        <tbody>
            @foreach($user->children_rel as $child)
                <tr>
                    <td>{{ $child->first_name }} {{ $child->last_name }}</td>
                    <td>{{ $child->group?->name ?? '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ===== API-TOKENS ===== --}}
<div class="section-header" style="background:#ea580c;">API-Tokens</div>
@if($user->tokens->count())
    <table>
        <thead><tr><th>Name / Gerät</th><th>Erstellt</th><th>Zuletzt genutzt</th></tr></thead>
        <tbody>
            @foreach($user->tokens as $token)
                <tr>
                    <td>{{ $token->name }}</td>
                    <td>{{ $token->created_at->format('d.m.Y H:i') }} Uhr</td>
                    <td>{{ $token->last_used_at ? $token->last_used_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine API-Tokens vorhanden.</div>
@endif

{{-- ===== PUSH ===== --}}
<div class="section-header" style="background:#e11d48;">Push-Benachrichtigungen</div>
<div style="padding:6px 8px;font-size:9px;">
    @php $pushCount = $user->pushSubscriptions->count(); @endphp
    Es {{ $pushCount == 1 ? 'wurde' : 'wurden' }}
    <strong>{{ $pushCount }} Gerät(e)</strong> registriert.
    Die gespeicherten Endpunkte enthalten keine persönlich zuordenbaren Geräteinformationen.
</div>

{{-- ===== KRANKMELDUNGEN ===== --}}
<div class="section-header" style="background:#dc2626;">Krankmeldungen</div>
@if($user->krankmeldungen->count())
    <table>
        <thead><tr><th>Kind</th><th>Von</th><th>Bis</th><th>Meldung</th><th>Eingereicht</th></tr></thead>
        <tbody>
            @foreach($user->krankmeldungen as $k)
                <tr>
                    <td>{{ $k->name }}</td>
                    <td>{{ $k->start->format('d.m.Y') }}</td>
                    <td>{{ $k->ende->format('d.m.Y') }}</td>
                    <td>{{ strip_tags($k->kommentar) }}</td>
                    <td>{{ $k->created_at->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Krankmeldungen gespeichert.</div>
@endif

{{-- ===== LISTENEINTRAGUNGEN ===== --}}
@php $listenTermine = $user->getListenTermine(); @endphp
<div class="section-header" style="background:#d97706;">Listeneintragungen</div>
@if($listenTermine && $listenTermine->count())
    <table>
        <thead><tr><th>Liste</th><th>Termin</th><th>Anmerkung</th><th>Reserviert am</th></tr></thead>
        <tbody>
            @foreach($listenTermine as $e)
                <tr>
                    <td>{{ $e->liste->listenname }}</td>
                    <td>{{ $e->termin ? $e->termin->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                    <td>{{ $e->comment ?? '–' }}</td>
                    <td>{{ $e->created_at ? $e->created_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Listeneintragungen gespeichert.</div>
@endif

{{-- ===== SCHICKZEITEN ===== --}}
@php
    $schickzeiten = $user->schickzeiten()->withTrashed()->get();
    $wochentage = ['1' => 'Mo', '2' => 'Di', '3' => 'Mi', '4' => 'Do', '5' => 'Fr'];
@endphp
<div class="section-header" style="background:#16a34a;">Schickzeiten</div>
@if($schickzeiten->count())
    <table>
        <thead><tr><th>Kind</th><th>Wochentag</th><th>Art</th><th>Uhrzeit</th><th>Erstellt</th><th>Gelöscht</th></tr></thead>
        <tbody>
            @foreach($schickzeiten as $s)
                <tr>
                    <td>{{ $s->child_name }}</td>
                    <td>{{ $wochentage[$s->weekday] ?? $s->weekday }}</td>
                    <td>{{ $s->type }}</td>
                    <td>{{ $s->time ? $s->time->format('H:i') . ' Uhr' : '–' }}</td>
                    <td>{{ $s->created_at ? $s->created_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                    <td>{{ $s->deleted_at ? $s->deleted_at->format('d.m.Y H:i') : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Schickzeiten gespeichert.</div>
@endif

{{-- ===== REINIGUNGSTERMINE ===== --}}
@if($user->Reinigung->count())
    <div class="section-header" style="background:#0891b2;">Reinigungstermine</div>
    <table>
        <thead><tr><th>Datum</th><th>Bereich / Aufgabe</th></tr></thead>
        <tbody>
            @foreach($user->Reinigung as $r)
                <tr>
                    <td>{{ $r->datum ? $r->datum->format('d.m.Y') : '–' }}</td>
                    <td>{{ $r->bereich }}: {{ $r->aufgabe }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ===== PFLICHTSTUNDEN ===== --}}
@php $pflichtstunden = $user->pflichtstunden()->withTrashed()->get(); @endphp
@if($pflichtstunden->count())
    <div class="section-header" style="background:#059669;">Pflichtstunden</div>
    <table>
        <thead><tr><th>Beschreibung</th><th>Von</th><th>Bis</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($pflichtstunden as $ps)
                @php
                    if ($ps->approved) {
                        $psStatus = '<span class="badge badge-green">Bestätigt</span>';
                    } elseif ($ps->rejected) {
                        $psStatus = '<span class="badge badge-red">Abgelehnt</span>';
                    } else {
                        $psStatus = '<span class="badge badge-amber">Ausstehend</span>';
                    }
                @endphp
                <tr>
                    <td>{{ $ps->description ?? '–' }}</td>
                    <td>{{ $ps->start ? $ps->start->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                    <td>{{ $ps->end ? $ps->end->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                    <td>{!! $psStatus !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ===== RÜCKMELDUNGEN ===== --}}
<div class="section-header" style="background:#0369a1;">Rückmeldungen</div>
@if($user->userRueckmeldung->count())
    <table>
        <thead><tr><th>Nachricht</th><th>Rückmeldung</th><th>Erstellt</th><th>Geändert</th></tr></thead>
        <tbody>
            @foreach($user->userRueckmeldung as $rm)
                <tr>
                    <td>{{ $rm->nachricht->header }}</td>
                    <td>{{ strip_tags($rm->text) }}</td>
                    <td>{{ $rm->created_at ? $rm->created_at->format('d.m.Y H:i') : '–' }}</td>
                    <td>{{ $rm->updated_at ? $rm->updated_at->format('d.m.Y H:i') : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Rückmeldungen gespeichert.</div>
@endif

{{-- ===== EIGENE BEITRÄGE ===== --}}
<div class="section-header" style="background:#15803d;">Eigene Beiträge / Nachrichten</div>
@if($user->own_posts->count())
    <table>
        <thead><tr><th>Überschrift</th><th>Erstellt</th></tr></thead>
        <tbody>
            @foreach($user->own_posts as $post)
                <tr>
                    <td>{{ $post->header }}</td>
                    <td>{{ $post->created_at ? $post->created_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine eigenen Beiträge vorhanden.</div>
@endif

{{-- ===== KOMMENTARE ===== --}}
<div class="section-header" style="background:#475569;">Kommentare</div>
@if($user->comments->count())
    <table>
        <thead><tr><th>Beitrag / Objekt</th><th>Kommentar</th><th>Erstellt</th></tr></thead>
        <tbody>
            @foreach($user->comments as $comment)
                <tr>
                    <td>{{ $comment->commentable?->header ?? '–' }}</td>
                    <td>{{ $comment->body }}</td>
                    <td>{{ $comment->created_at ? $comment->created_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Kommentare gespeichert.</div>
@endif

{{-- ===== DISKUSSIONEN ===== --}}
<div class="section-header" style="background:#6d28d9;">Diskussionen (Elternratsbereich)</div>
@if($user->discussions->count())
    <table>
        <thead><tr><th>Überschrift</th><th>Beitrag</th><th>Erstellt</th></tr></thead>
        <tbody>
            @foreach($user->discussions as $disc)
                <tr>
                    <td>{{ $disc->header }}</td>
                    <td>{{ strip_tags($disc->text) }}</td>
                    <td>{{ $disc->created_at->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Diskussionsbeiträge vorhanden.</div>
@endif

{{-- ===== ABFRAGEN / ABSTIMMUNGEN ===== --}}
<div class="section-header" style="background:#a21caf;">Abfragen / Abstimmungen</div>
<div style="padding:4px 8px;font-size:8px;background:#fdf4ff;color:#701a75;border-bottom:1px solid #e9d5ff;">
    Gespeichert wird nur die Teilnahme – die gewählten Antworten werden anonym ohne Benutzerreferenz abgelegt.
</div>
@php $pollVotes = $user->pollVotes()->with(['poll.post', 'poll'])->get(); @endphp
@if($pollVotes->count())
    <table>
        <thead><tr><th>Nachricht</th><th>Abfrage-Titel</th><th>Teilgenommen am</th><th>Antwort</th></tr></thead>
        <tbody>
            @foreach($pollVotes as $vote)
                <tr>
                    <td>{{ $vote->poll?->post?->header ?? '–' }}</td>
                    <td>{{ $vote->poll?->poll_name ?? '–' }}</td>
                    <td>{{ $vote->created_at ? $vote->created_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                    <td><em>anonym gespeichert</em></td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Abstimmungen gespeichert.</div>
@endif

{{-- ===== LESEBESTÄTIGUNGEN ===== --}}
<div class="section-header" style="background:#0284c7;">Lesebestätigungen</div>
@if($user->read_receipts->count())
    <table>
        <thead><tr><th>Beitrag</th><th>Bestätigt am</th></tr></thead>
        <tbody>
            @foreach($user->read_receipts as $receipt)
                <tr>
                    <td>{{ $receipt->post?->header ?? '–' }}</td>
                    <td>{{ $receipt->created_at->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Lesebestätigungen vorhanden.</div>
@endif

{{-- ===== MESSENGER: KONVERSATIONEN ===== --}}
@php $userConversations = \App\Model\Conversation::forUser($user->id)->with('group')->get(); @endphp
<div class="section-header" style="background:#0d9488;">Messenger – Konversationen</div>
@if($userConversations->count())
    <table>
        <thead><tr><th>Typ</th><th>Titel / Gruppe</th><th>Erstellt</th></tr></thead>
        <tbody>
            @foreach($userConversations as $conv)
                <tr>
                    <td>{{ $conv->type === 'group' ? 'Gruppen-Chat' : 'Direktnachricht' }}</td>
                    <td>{{ $conv->title ?? $conv->group?->name ?? 'Direktnachricht' }}</td>
                    <td>{{ $conv->created_at->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Messenger-Konversationen vorhanden.</div>
@endif

{{-- ===== MESSENGER: GESENDETE NACHRICHTEN ===== --}}
@php $sentMessages = \App\Model\Message::where('sender_id', $user->id)->withTrashed()->with('conversation')->orderByDesc('created_at')->get(); @endphp
<div class="section-header" style="background:#0891b2;">Messenger – Gesendete Nachrichten</div>
<div style="padding:4px 8px;font-size:8px;background:#ecfeff;color:#155e75;border-bottom:1px solid #a5f3fc;">
    Nur von Ihnen gesendete Nachrichten. Empfangene Nachrichten anderer Nutzer werden nicht aufgeführt.
</div>
@if($sentMessages->count())
    <table>
        <thead><tr><th>Konversation</th><th>Nachricht</th><th>Gesendet</th><th>Status</th></tr></thead>
        <tbody>
            @foreach($sentMessages as $msg)
                <tr>
                    <td>{{ $msg->conversation?->title ?? 'ID ' . $msg->conversation_id }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($msg->body, 80) }}</td>
                    <td>{{ $msg->created_at->format('d.m.Y H:i') }} Uhr</td>
                    <td>
                        @if($msg->deleted_at)
                            <span class="badge badge-red">gelöscht</span>
                        @elseif($msg->edited_at)
                            <span class="badge badge-amber">bearbeitet</span>
                        @else
                            –
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <div class="empty">Keine Messenger-Nachrichten gesendet.</div>
@endif

{{-- ===== MESSENGER: MELDUNGEN ===== --}}
@php $userReports = \App\Model\MessageReport::where('reporter_id', $user->id)->get(); @endphp
@if($userReports->count())
    <div class="section-header" style="background:#ea580c;">Messenger – Ihre Meldungen</div>
    <table>
        <thead><tr><th>Grund</th><th>Gemeldet am</th><th>Aufgelöst am</th></tr></thead>
        <tbody>
            @foreach($userReports as $report)
                <tr>
                    <td>{{ $report->reason }}</td>
                    <td>{{ $report->created_at->format('d.m.Y H:i') }} Uhr</td>
                    <td>{{ $report->resolved_at ? \Carbon\Carbon::parse($report->resolved_at)->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ===== BEITRAGSMELDUNGEN ===== --}}
@php $postReports = \App\Model\PostReport::where('reporter_id', $user->id)->with('post')->get(); @endphp
@if($postReports->count())
    <div class="section-header" style="background:#dc2626;">Beitragsmeldungen</div>
    <table>
        <thead><tr><th>Beitrag</th><th>Grund</th><th>Gemeldet am</th><th>Aufgelöst am</th></tr></thead>
        <tbody>
            @foreach($postReports as $report)
                <tr>
                    <td>{{ $report->post?->header ?? '[gelöscht]' }}</td>
                    <td>{{ $report->reason }}</td>
                    <td>{{ $report->created_at->format('d.m.Y H:i') }} Uhr</td>
                    <td>{{ $report->resolved_at ? $report->resolved_at->format('d.m.Y H:i') . ' Uhr' : '–' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ===== ERINNERUNGSLOGS ===== --}}
@php $reminderLogs = \App\Model\ReminderLog::where('user_id', $user->id)->with('post')->orderByDesc('sent_at')->get(); @endphp
@if($reminderLogs->count())
    <div class="section-header" style="background:#d97706;">Automatische Erinnerungen</div>
    <div style="padding:4px 8px;font-size:8px;background:#fffbeb;color:#92400e;border-bottom:1px solid #fde68a;">
        Protokoll automatisch gesendeter Erinnerungen für Rückmeldungen, Lesebestätigungen und Anwesenheitsabfragen.
    </div>
    <table>
        <thead><tr><th>Typ</th><th>Nachricht</th><th>Stufe</th><th>Kanal</th><th>Gesendet</th></tr></thead>
        <tbody>
            @foreach($reminderLogs as $log)
                <tr>
                    <td>
                        @if(str_contains($log->remindable_type, 'Rueckmeldungen'))
                            Rückmeldung
                        @elseif(str_contains($log->remindable_type, 'Post'))
                            Lesebestätigung
                        @elseif(str_contains($log->remindable_type, 'ChildCheckIn'))
                            Anwesenheit
                        @else
                            {{ class_basename($log->remindable_type) }}
                        @endif
                    </td>
                    <td>{{ $log->post?->header ?? '–' }}</td>
                    <td>Stufe {{ $log->level }}</td>
                    <td>{{ $log->channel }}</td>
                    <td>{{ $log->sent_at->format('d.m.Y H:i') }} Uhr</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ===== ABSCHLUSS ===== --}}
<div style="margin-top:20px;padding:8px 12px;background:#fefce8;border-left:4px solid #ca8a04;font-size:9px;color:#713f12;">
    <strong>Hinweis:</strong>
    Bestimmte Aktionen (z.&nbsp;B. Kontoänderungen, Schickzeiten) werden in einem internen Änderungsprotokoll (Audit-Log) festgehalten.
    Dieses dient der Nachvollziehbarkeit und Sicherheit und wird ausschließlich von Administratoren eingesehen.
    Auf Anfrage beim Datenschutzbeauftragten erhalten Sie auch darüber Auskunft.
</div>

</body>
</html>

