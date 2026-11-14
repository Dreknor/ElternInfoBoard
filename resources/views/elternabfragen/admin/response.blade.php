@extends('layouts.app')

@section('title', '| Elternabfragen · Antwort')

@section('content')
<div class="container-fluid px-4 py-6 max-w-4xl">
    @include('elternabfragen.admin._nav', [
        'title' => trim(($response->child?->first_name ?? '').' '.($response->child?->last_name ?? '')),
        'subtitle' => $response->inquiry->title.' · Version '.$response->version,
        'icon' => 'fas fa-file-alt',
    ])

    <section class="ea-card mb-6">
        <div class="ea-card-header flex-wrap">
            @if($response->is_current) <x-elternabfragen.badge color="green" icon="fas fa-check">aktuell gültige Version</x-elternabfragen.badge>
            @else <x-elternabfragen.badge color="slate">ersetzte Version</x-elternabfragen.badge> @endif
            <x-elternabfragen.badge :color="$response->review_status->color()" dot>{{ $response->review_status->label() }}</x-elternabfragen.badge>
            <span class="text-sm ea-muted">{{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }} · {{ $response->assignment?->group?->name }}</span>
        </div>
        <div class="ea-card-body">
            @if($response->correction_reason)<p class="text-sm ea-muted mb-3"><i class="fas fa-redo" aria-hidden="true"></i> Korrekturgrund: {{ $response->correction_reason }}</p>@endif
            @if($response->review_note)
                <div class="ea-alert ea-alert-warning mb-3"><i class="fas fa-comment-dots" aria-hidden="true"></i><p class="mb-0">Prüfvermerk: {{ $response->review_note }} ({{ $response->reviewer?->name }}, {{ $response->reviewed_at?->format('d.m.Y') }})</p></div>
            @endif

            {{-- Nur Sekretariat: alte Werte stammen aus der Vorabfrage --}}
            @if($canReview && $response->baselineChanges() !== [])
                @php $byKey = collect($fields)->filter(fn ($f) => isset($f['key']))->keyBy('key'); @endphp
                <div class="ea-alert ea-alert-warning mb-4"><i class="fas fa-history" aria-hidden="true"></i>
                    <div>
                        <p class="font-semibold mb-1">Geändert gegenüber der Vorabfrage</p>
                        <ul class="space-y-0.5 mb-0">
                            @foreach($response->baselineChanges() as $key => $change)
                                @php $field = $byKey->get($key, ['key' => $key, 'type' => 'text', 'label' => $key]); @endphp
                                <li>{{ $field['label'] }}: <span class="opacity-70 line-through">{{ $schema->display($field, $change['old']) }}</span> → <strong>{{ $schema->display($field, $change['new']) }}</strong></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @elseif($canReview && $response->hasBaseline())
                <p class="mb-4 text-sm text-emerald-700 dark:text-emerald-400"><i class="fas fa-equals" aria-hidden="true"></i> Angaben unverändert aus der Vorabfrage übernommen.</p>
            @endif

            @include('elternabfragen.parent._answers', [
                'fields' => $fields,
                'answers' => $response->answers,
                'fileRoute' => fn ($key) => route('elternabfragen.admin.responses.file', [$response, $key]),
            ])
        </div>
        <div class="ea-card-footer">
            <p class="text-xs ea-muted mb-0 font-mono break-all"><i class="fas fa-fingerprint" aria-hidden="true"></i> Formular-Hash {{ $response->form_hash }} · Antwort-Hash {{ $response->answer_hash }}</p>
        </div>
    </section>

    @if($canReview)
        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-clipboard-check" aria-hidden="true"></i></div>
                <div><h2 class="ea-card-title">Prüfung</h2><p class="ea-card-sub">Nur geprüfte Antworten sind für Mitarbeitende sichtbar (sofern die Abfrage dafür freigegeben ist).</p></div>
            </div>
            <form method="POST" action="{{ route('elternabfragen.admin.responses.review', $response) }}" class="ea-card-body space-y-4">
                @csrf
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach(\App\Enums\InquiryReviewStatus::cases() as $case)
                        <label class="ea-choice"><input type="radio" name="review_status" value="{{ $case->value }}" @checked($response->review_status === $case)> <span class="font-medium ea-text">{{ $case->label() }}</span></label>
                    @endforeach
                </div>
                <textarea name="review_note" rows="2" class="ea-input" placeholder="Vermerk (bei Klärungsbedarf Pflicht)">{{ $response->review_note }}</textarea>
                <button class="ea-btn ea-btn-primary"><i class="fas fa-save" aria-hidden="true"></i> Speichern</button>
            </form>
        </section>
    @endif
</div>
@endsection
