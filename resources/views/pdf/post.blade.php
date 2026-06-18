<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>{{ $post->header }}</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            background: #ffffff;
        }

        /* ── Seitenkopf (fest oben, einzeilig) ──────────────────────── */
        .page-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 22px;
            background: #1d4ed8;
            color: #ffffff;
            padding: 0 20px;
        }
        .page-header table {
            width: 100%;
            border-collapse: collapse;
            height: 22px;
        }
        .page-header td {
            padding: 0;
            vertical-align: middle;
        }
        .page-header .app-name {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }
        .page-header .export-info {
            font-size: 8px;
            opacity: 0.85;
            text-align: right;
            white-space: nowrap;
        }

        /* ── Seitenfuß (fest unten) ──────────────────────────────────── */
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 18px;
            border-top: 1px solid #e2e8f0;
            padding: 0 20px;
            font-size: 8px;
            color: #94a3b8;
            background: #f8fafc;
        }
        .page-footer table {
            width: 100%;
            border-collapse: collapse;
            height: 18px;
        }
        .page-footer td {
            padding: 0;
            vertical-align: middle;
        }
        .page-footer .page-right {
            text-align: right;
            white-space: nowrap;
        }
        .page-footer .page-num:after {
            content: counter(page);
        }
        .page-footer .page-total:before {
            content: counter(pages);
        }

        /* ── Inhalt-Wrapper ──────────────────────────────────────────── */
        .content-wrap {
            margin-top: 28px;
            margin-bottom: 26px;
            padding: 0 20px;
        }

        /* ── Post-Kopfbereich ────────────────────────────────────────── */
        .post-meta-bar {
            border-left: 4px solid #1d4ed8;
            padding: 8px 14px;
            background: #eff6ff;
            margin-bottom: 18px;
            width: 100%;
        }
        .post-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0 0 6px 0;
            line-height: 1.1;
        }
        .post-meta-row {
            font-size: 9px;
            color: #475569;
            margin-top: 4px;
        }
        .post-meta-row span {
            margin-right: 14px;
        }

        /* ── Gruppen-Badges ──────────────────────────────────────────── */
        .groups-row {
            margin-bottom: 14px;
        }
        .group-badge {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            border-radius: 10px;
            padding: 2px 9px;
            font-size: 9px;
            font-weight: bold;
            margin-right: 5px;
            margin-bottom: 4px;
        }

        /* ── Trennlinie ──────────────────────────────────────────────── */
        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 14px 0;
        }

        /* ── Hauptinhalt ─────────────────────────────────────────────── */
        .post-body {
            font-size: 11px;
            color: #1f2937;
            line-height: 1.7;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* HTML-Inhalt aus dem Rich-Text-Editor */
        .post-body h1, .post-body h2, .post-body h3,
        .post-body h4, .post-body h5, .post-body h6 {
            color: #1e3a8a;
            margin: 12px 0 6px 0;
            font-weight: bold;
            page-break-after: avoid;
        }
        .post-body h1 { font-size: 15px; }
        .post-body h2 { font-size: 12px; }
        .post-body h3 { font-size: 11px; }
        .post-body h4, .post-body h5, .post-body h6 { font-size: 10px; }

        .post-body p {
            margin: 0 0 10px 0;
        }

        .post-body ul, .post-body ol {
            margin: 6px 0 10px 0;
            padding-left: 22px;
        }
        .post-body li {
            margin-bottom: 4px;
        }

        .post-body blockquote {
            border-left: 3px solid #93c5fd;
            margin: 10px 0;
            padding: 6px 12px;
            background: #eff6ff;
            color: #1e40af;
            font-style: italic;
        }

        .post-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 10px;
        }
        .post-body table th {
            background: #f1f5f9;
            color: #374151;
            font-weight: bold;
            text-align: left;
            padding: 5px 8px;
            border: 1px solid #cbd5e1;
        }
        .post-body table td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        .post-body table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .post-body img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 8px auto;
        }

        .post-body a {
            color: #2563eb;
            text-decoration: underline;
            word-break: break-all;
        }

        .post-body strong, .post-body b { font-weight: bold; }
        .post-body em, .post-body i { font-style: italic; }
        .post-body u { text-decoration: underline; }
        .post-body s { text-decoration: line-through; }

        .post-body pre, .post-body code {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 3px;
            padding: 2px 5px;
            font-size: 10px;
            font-family: Courier New, monospace;
        }
        .post-body pre {
            padding: 8px 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }

        /* ── Anhang-Sektion ──────────────────────────────────────────── */
        .attachments-section {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #e2e8f0;
            page-break-inside: avoid;
        }
        .attachments-title {
            font-size: 11px;
            font-weight: bold;
            color: #374151;
            margin-bottom: 8px;
        }
        .attachment-item {
            display: block;
            padding: 5px 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            margin-bottom: 4px;
            font-size: 10px;
            color: #374151;
        }
        .attachment-item .icon {
            color: #6b7280;
            margin-right: 5px;
        }

        /* ── Rückmeldungs-Hinweis ────────────────────────────────────── */
        .rueckmeldung-hint {
            margin-top: 16px;
            padding: 8px 12px;
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            font-size: 10px;
            color: #92400e;
            page-break-inside: avoid;
        }

        /* ── Unveröffentlicht-Hinweis ────────────────────────────────── */
        .unpublished-hint {
            margin-bottom: 12px;
            padding: 6px 12px;
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            font-size: 10px;
            color: #991b1b;
        }

        /* ── Seitenumbruch-Kontrolle ─────────────────────────────────── */
        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>

{{-- ── Fester Seitenkopf ──────────────────────────────────────────────── --}}
<div class="page-header">
    <table><tr>
        <td class="app-name">{{ config('app.name') }}</td>
        <td class="export-info">
            Erstellt am {{ $exportAt->format('d.m.Y') }} um {{ $exportAt->format('H:i') }} Uhr
            &nbsp;&bull;&nbsp; {{ auth()->user()->name }}
        </td>
    </tr></table>
</div>

{{-- ── Fester Seitenfuß ───────────────────────────────────────────────── --}}
<div class="page-footer">
    <table><tr>
        <td>{{ config('app.name') }} &nbsp;&bull;&nbsp; {{ $post->header }}</td>
        <td class="page-right">
            Seite <span class="page-num"></span> von <span class="page-total"></span>
        </td>
    </tr></table>
</div>

{{-- ── Hauptinhalt ─────────────────────────────────────────────────────── --}}
<div class="content-wrap">



    {{-- Post-Metadaten --}}
    <div class="post-meta-bar no-break">
        <div class="post-title">{{ $post->header }}</div>
        <div class="post-meta-row">
            <span>&#128100; {{ $post->autor?->name ?? config('app.name') }}</span>
            <span>&#128197; Erstellt: {{ $post->created_at->format('d.m.Y') }}</span>
            <span>&#128260; Aktualisiert: {{ $post->updated_at->format('d.m.Y H:i') }}</span>
            @if($post->archiv_ab)
                <span>&#128230; Archiv ab: {{ $post->archiv_ab->format('d.m.Y') }}</span>
            @endif
        </div>
    </div>

    {{-- Gruppen --}}
    @if($post->groups->isNotEmpty())
        <div class="groups-row">
            @foreach($post->groups as $group)
                <span class="group-badge">{{ $group->name }}</span>
            @endforeach
        </div>
    @endif

    <hr class="divider">

    {{-- Eigentlicher Post-Inhalt --}}
    <div class="post-body">
        {!! $post->news !!}
    </div>

    {{-- Dateianhänge --}}
    @if($post->getMedia('files')->isNotEmpty())
        <div class="attachments-section no-break">
            <div class="attachments-title">&#128206; Dateianhänge ({{ $post->getMedia('files')->count() }})</div>
            @foreach($post->getMedia('files') as $file)
                <span class="attachment-item">
                    <span class="icon">&#128196;</span>{{ $file->file_name }}
                    @if($file->size)
                        &nbsp;({{ number_format($file->size / 1024, 1) }} KB)
                    @endif
                </span>
            @endforeach
        </div>
    @endif

    {{-- Rückmeldungs-Hinweis --}}
    @if($post->rueckmeldung && $post->rueckmeldung->ende)
        <div class="rueckmeldung-hint no-break">
            &#9888; Rückmeldung erforderlich bis:
            <strong>{{ $post->rueckmeldung->ende->format('d.m.Y') }}</strong>
            @if($post->rueckmeldung->pflicht)
                &nbsp;&mdash; <strong>Pflichtangabe</strong>
            @endif
        </div>
    @endif

</div>

</body>
</html>

