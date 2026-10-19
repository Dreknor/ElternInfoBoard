@extends('layouts.app')

@section('title', '| Elternabfragen · Freigaben')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Freigaben', 'subtitle' => 'Keine Vorlage und keine Abfrage wird ohne Prüfung verwendet bzw. versendet'])

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="bg-white rounded-xl shadow-sm border border-gray-200">
            <h2 class="px-4 py-3 border-b font-semibold text-gray-700"><i class="fas fa-layer-group" aria-hidden="true"></i> Vorlagen in Prüfung ({{ $versions->count() }})</h2>
            <ul class="divide-y divide-gray-100">
                @forelse($versions as $version)
                    <li class="px-4 py-3 flex items-center justify-between gap-3">
                        <div>
                            <a href="{{ route('elternabfragen.admin.templates.show', $version) }}" class="font-medium text-blue-700 hover:underline">{{ $version->label() }}</a>
                            <div class="text-xs text-gray-500">
                                eingereicht {{ $version->submitted_at?->format('d.m.Y H:i') }} von {{ $version->creator?->name ?? '–' }}
                                @if($version->is_sensitive) · <span class="text-red-700">sensibel – Zusatzprüfung</span> @endif
                            </div>
                        </div>
                        <a href="{{ route('elternabfragen.admin.templates.show', $version) }}#pruefung" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm">Prüfen</a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500 text-sm">Keine Vorlagen zur Prüfung.</li>
                @endforelse
            </ul>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-gray-200">
            <h2 class="px-4 py-3 border-b font-semibold text-gray-700"><i class="fas fa-paper-plane" aria-hidden="true"></i> Abfragen vor dem Versand ({{ $inquiries->count() }})</h2>
            <ul class="divide-y divide-gray-100">
                @forelse($inquiries as $inquiry)
                    <li class="px-4 py-3 flex items-center justify-between gap-3">
                        <div>
                            <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="font-medium text-blue-700 hover:underline">{{ $inquiry->title }}</a>
                            <div class="text-xs text-gray-500">
                                {{ $inquiry->targetLabel() }} · Start {{ $inquiry->starts_at->format('d.m.Y H:i') }} · Frist {{ $inquiry->deadline_at->format('d.m.Y') }}
                                · von {{ $inquiry->creator?->name ?? '–' }}
                            </div>
                        </div>
                        <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}#pruefung" class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm">Prüfen</a>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-gray-500 text-sm">Keine Abfragen zur Freigabe.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
