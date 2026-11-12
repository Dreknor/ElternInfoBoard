{{-- Erklärungsquittungen der Abfrage: Kettenprüfung und Quittungen je Kind. Erwartet: $inquiry, $receipts (ReceiptController::overview) --}}
<section id="quittungen" class="ea-card overflow-hidden scroll-mt-4">
    <div class="ea-card-header">
        <div class="ea-card-icon"><i class="fas fa-stamp" aria-hidden="true"></i></div>
        <div class="flex-1">
            <h2 class="ea-card-title">Quittungen</h2>
            <p class="ea-card-sub">Nachweis jeder Erklärung der Eltern (§ 126b BGB) – Prüfsummen über Wortlaut und Antworten, als Kette verbunden</p>
        </div>
        <a href="{{ route('elternabfragen.admin.receipts.check') }}" class="ea-btn ea-btn-secondary ea-btn-sm"><i class="fas fa-search" aria-hidden="true"></i> PDF prüfen</a>
    </div>
    <div class="ea-card-body space-y-3 text-sm">
        @if($receipts['count'] === 0)
            <p class="mb-0 ea-muted">Noch keine Quittungen.</p>
        @elseif($receipts['chain']['valid'])
            <div class="ea-alert ea-alert-success" role="status"><i class="fas fa-link" aria-hidden="true"></i>
                <p class="mb-0"><strong>Kette in Ordnung:</strong> {{ $receipts['count'] }} {{ $receipts['count'] === 1 ? 'Quittung' : 'Quittungen' }}, lückenlos und unverändert.
                    @if($receipts['backfilled'] > 0) Davon {{ $receipts['backfilled'] }} nacherfasst (Erklärungen aus der Zeit vor den Quittungen). @endif</p>
            </div>
        @else
            <div class="ea-alert ea-alert-danger" role="alert"><i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <div>
                    <p class="mb-1"><strong>Kette fehlerhaft:</strong> {{ count($receipts['chain']['errors']) }} Fehler bei {{ $receipts['count'] }} Quittungen. Daten wurden möglicherweise außerhalb der Anwendung verändert – bitte die Administration informieren.</p>
                    <ul class="list-disc pl-5 mb-0">
                        @foreach($receipts['chain']['errors'] as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
    </div>
    @if($receipts['count'] > 0)
        <div class="overflow-x-auto">
            <table class="ea-table">
                <thead>
                    <tr><th>Schüler/in</th><th>Erklärung</th><th>Erklärende Person</th><th>Zeitpunkt</th><th><span class="sr-only">Download</span></th></tr>
                </thead>
                <tbody>
                    @foreach($receipts['children'] as $child)
                        @foreach($child['receipts'] as $receipt)
                            <tr>
                                @if($loop->first)
                                    <td rowspan="{{ $child['receipts']->count() }}" class="align-top">
                                        <span class="font-medium">{{ $child['child'] }}</span>
                                        <span class="block text-xs ea-muted">{{ $child['group'] ?? 'ohne Klasse' }}</span>
                                    </td>
                                @endif
                                <td>
                                    {{ $receipt->kind->label() }}
                                    <span class="block text-xs ea-muted">Nr. {{ $receipt->sequence }} · Version {{ $receipt->response_version }}@if($receipt->backfilled) · nacherfasst @endif</span>
                                </td>
                                <td>{{ $receipt->declarant_name }}</td>
                                <td class="whitespace-nowrap">{{ $receipt->declared_at->copy()->setTimezone(\App\Services\Elternabfragen\ReceiptService::TIMEZONE)->format('d.m.Y H:i') }}</td>
                                <td class="text-right">
                                    <a href="{{ route('elternabfragen.admin.receipts.download', $receipt) }}" class="ea-btn ea-btn-ghost ea-btn-sm" title="Zweitschrift der Quittung {{ $receipt->uuid }}">
                                        <i class="fas fa-download" aria-hidden="true"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
