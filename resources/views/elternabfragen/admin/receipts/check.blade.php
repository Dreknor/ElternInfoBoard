@extends('layouts.app')

@section('title', '| Elternabfragen · Quittung prüfen')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', [
        'title' => 'Quittung prüfen',
        'subtitle' => 'Ist eine vorgelegte Erklärungsquittung echt und unverändert?',
        'icon' => 'fas fa-stamp',
    ])

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 space-y-6 min-w-0">
            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-file-pdf" aria-hidden="true"></i></div>
                    <div class="flex-1">
                        <h2 class="ea-card-title">PDF hochladen</h2>
                        <p class="ea-card-sub">Die Datei wird nicht gespeichert – geprüft wird nur ihre Prüfsumme.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('elternabfragen.admin.receipts.verify') }}" enctype="multipart/form-data" class="ea-card-body space-y-3">
                    @csrf
                    <div>
                        <label for="pdf" class="ea-label">Quittung (PDF, höchstens 10 MB)</label>
                        <input id="pdf" type="file" name="pdf" accept="application/pdf,.pdf" required class="ea-input">
                        @error('pdf')<p class="text-sm text-red-600 mt-1 mb-0">{{ $message }}</p>@enderror
                    </div>
                    <button class="ea-btn ea-btn-primary"><i class="fas fa-search" aria-hidden="true"></i> Prüfen</button>
                </form>
            </section>

            @if($checked)
                @if($receipt)
                    <section class="ea-card" aria-live="polite">
                        <div class="ea-card-header">
                            <div class="ea-card-icon !bg-emerald-100 !text-emerald-700 dark:!bg-emerald-900/40 dark:!text-emerald-300"><i class="fas fa-check" aria-hidden="true"></i></div>
                            <div class="flex-1">
                                <h2 class="ea-card-title">Echt und unverändert</h2>
                                <p class="ea-card-sub">Das PDF stimmt Byte für Byte mit der ausgestellten Quittung überein.</p>
                            </div>
                        </div>
                        <dl class="ea-facts px-5 py-2 mb-0">
                            <div><dt><i class="fas fa-hashtag" aria-hidden="true"></i> Quittung</dt><dd class="break-all">{{ $receipt->uuid }} <span class="ea-muted">(Nr. {{ $receipt->sequence }})</span></dd></div>
                            <div><dt><i class="fas fa-paper-plane" aria-hidden="true"></i> Abfrage</dt><dd>
                                @if($receipt->inquiry && ! $receipt->inquiry->trashed())
                                    <a href="{{ route('elternabfragen.admin.inquiries.show', $receipt->inquiry_id) }}" class="ea-link">{{ $receipt->inquiry->title }}</a>
                                @else
                                    {{ $receipt->inquiry?->title ?? '–' }}
                                @endif
                            </dd></div>
                            <div><dt><i class="fas fa-child" aria-hidden="true"></i> Kind</dt><dd>{{ $receipt->child_name }}@if($receipt->assignment?->group) <span class="ea-muted">· {{ $receipt->assignment->group->name }}</span>@endif</dd></div>
                            <div><dt><i class="fas fa-user" aria-hidden="true"></i> Erklärende Person</dt><dd>{{ $receipt->declarant_name }}</dd></div>
                            <div><dt><i class="far fa-clock" aria-hidden="true"></i> Zeitpunkt</dt><dd>{{ $receipt->declared_at->copy()->setTimezone(\App\Services\Elternabfragen\ReceiptService::TIMEZONE)->format('d.m.Y, H:i:s') }} Uhr</dd></div>
                            <div><dt><i class="fas fa-tag" aria-hidden="true"></i> Art</dt><dd>{{ $receipt->kind->label() }}@if($receipt->backfilled) <span class="ea-muted">(nacherfasst)</span>@endif</dd></div>
                        </dl>
                        <div class="ea-card-body space-y-3 text-sm">
                            @if($chain['valid'])
                                <p class="mb-0 text-emerald-700 dark:text-emerald-400"><i class="fas fa-link" aria-hidden="true"></i> Die Quittungskette der Abfrage ist vollständig und unverändert ({{ $chain['count'] }} {{ $chain['count'] === 1 ? 'Quittung' : 'Quittungen' }}).</p>
                            @else
                                <div class="ea-alert ea-alert-danger" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                                    <p class="mb-0">Die Quittungskette der Abfrage ist auffällig ({{ count($chain['errors']) }} Fehler). Details auf der <a href="{{ route('elternabfragen.admin.inquiries.show', $receipt->inquiry_id) }}#quittungen" class="ea-link font-semibold">Seite der Abfrage</a>.</p>
                                </div>
                            @endif
                            @if($newer > 0)
                                <div class="ea-alert ea-alert-warning"><i class="fas fa-history" aria-hidden="true"></i>
                                    <p class="mb-0">Für dieses Kind gibt es {{ $newer === 1 ? 'eine spätere Erklärung' : $newer.' spätere Erklärungen' }} (z. B. Korrektur, Bestätigung oder Widerruf). Die Quittung belegt die Erklärung zu ihrem Zeitpunkt – nicht, dass sie noch gilt.</p>
                                </div>
                            @endif
                            <a href="{{ route('elternabfragen.admin.receipts.download', $receipt) }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-download" aria-hidden="true"></i> Zweitschrift herunterladen</a>
                        </div>
                    </section>
                @else
                    <div class="ea-alert ea-alert-danger" role="alert"><i class="fas fa-times-circle" aria-hidden="true"></i>
                        <p class="mb-0"><strong>Nicht bekannt oder verändert.</strong> Zu dieser Datei gibt es keine Quittung. Entweder wurde sie nicht von dieser Schule ausgestellt, nachträglich verändert (auch Drucken als PDF oder erneutes Speichern ändert die Datei) oder die Antworten der Abfrage sind bereits gelöscht.</p>
                    </div>
                @endif
            @endif
        </div>

        <aside class="space-y-6">
            <section class="ea-card">
                <div class="ea-card-header">
                    <div class="ea-card-icon"><i class="fas fa-question" aria-hidden="true"></i></div>
                    <h2 class="ea-card-title">Was wird geprüft?</h2>
                </div>
                <div class="ea-card-body text-sm space-y-2 ea-text">
                    <p class="mb-0">Zu jeder Erklärung der Eltern (Abgabe, Korrektur, Bestätigung, Widerruf) speichert das System eine Prüfsumme des ausgestellten PDFs. Die Prüfung vergleicht die hochgeladene Datei damit.</p>
                    <p class="mb-0"><strong>Echt</strong> heißt: Die Datei ist genau die Quittung, die das System ausgestellt hat – kein Wort und keine Antwort wurde geändert.</p>
                    <p class="mb-0">Nur die Original-PDF-Datei lässt sich prüfen, kein Ausdruck oder Scan.</p>
                </div>
            </section>
        </aside>
    </div>
</div>
@endsection
