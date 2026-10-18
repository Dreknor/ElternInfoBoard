@extends('layouts.app')

@section('title', '| Elternabfragen · Antwort')

@section('content')
<div class="container-fluid px-4 py-6 max-w-4xl">
    @include('elternabfragen.admin._nav', [
        'title' => trim(($response->child?->first_name ?? '').' '.($response->child?->last_name ?? '')),
        'subtitle' => $response->inquiry->title.' · Version '.$response->version,
    ])

    <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <div class="flex flex-wrap items-center gap-2 text-sm mb-3">
            @if($response->is_current) <x-elternabfragen.badge color="green">aktuell gültige Version</x-elternabfragen.badge>
            @else <x-elternabfragen.badge color="slate">ersetzte Version</x-elternabfragen.badge> @endif
            <x-elternabfragen.badge :color="$response->review_status->color()">{{ $response->review_status->label() }}</x-elternabfragen.badge>
            <span class="text-gray-600">{{ $response->submitted_at->format('d.m.Y H:i') }} · {{ $response->user?->name ?? 'unbekannt' }} · {{ $response->assignment?->group?->name }}</span>
        </div>
        @if($response->correction_reason)<p class="text-sm text-gray-600 mb-2">Korrekturgrund: {{ $response->correction_reason }}</p>@endif
        @if($response->review_note)<p class="text-sm bg-amber-50 p-2 rounded mb-2">Prüfvermerk: {{ $response->review_note }} ({{ $response->reviewer?->name }}, {{ $response->reviewed_at?->format('d.m.Y') }})</p>@endif

        @include('elternabfragen.parent._answers', [
            'fields' => $fields,
            'answers' => $response->answers,
            'fileRoute' => fn ($key) => route('elternabfragen.admin.responses.file', [$response, $key]),
        ])

        <p class="text-xs text-gray-500 mt-4 font-mono break-all">Formular-Hash {{ $response->form_hash }} · Antwort-Hash {{ $response->answer_hash }}</p>
    </section>

    @if($canReview)
        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <h2 class="font-semibold text-gray-800 mb-2">Prüfung</h2>
            <p class="text-sm text-gray-600 mb-3">Nur geprüfte Antworten sind für Mitarbeitende sichtbar (sofern die Abfrage dafür freigegeben ist).</p>
            <form method="POST" action="{{ route('elternabfragen.admin.responses.review', $response) }}" class="space-y-3">
                @csrf
                <div class="flex flex-wrap gap-4 text-sm">
                    @foreach(\App\Enums\InquiryReviewStatus::cases() as $case)
                        <label class="flex items-center gap-2"><input type="radio" name="review_status" value="{{ $case->value }}" @checked($response->review_status === $case)> {{ $case->label() }}</label>
                    @endforeach
                </div>
                <textarea name="review_note" rows="2" class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Vermerk (bei Klärungsbedarf Pflicht)">{{ $response->review_note }}</textarea>
                <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Speichern</button>
            </form>
        </section>
    @endif
</div>
@endsection
