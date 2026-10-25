@extends('layouts.app')

@section('title', '| Elternabfragen · Freigaben')

@section('content')
<div class="container-fluid px-4 py-6">
    @include('elternabfragen.admin._nav', ['title' => 'Freigaben', 'subtitle' => 'Keine Vorlage und keine Abfrage wird ohne Prüfung verwendet bzw. versendet', 'icon' => 'fas fa-user-check'])

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-layer-group" aria-hidden="true"></i></div>
                <div class="flex-1"><h2 class="ea-card-title">Vorlagen in Prüfung</h2><p class="ea-card-sub">Inhalt, Zweck und Datenschutz prüfen</p></div>
                <span class="ea-tab-count {{ $versions->count() ? '' : '!bg-gray-400' }}">{{ $versions->count() }}</span>
            </div>
            <ul class="ea-divide mb-0">
                @forelse($versions as $version)
                    <li class="px-5 py-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('elternabfragen.admin.templates.show', $version) }}" class="ea-link">{{ $version->label() }}</a>
                            <div class="text-xs ea-muted mt-0.5">
                                eingereicht {{ $version->submitted_at?->format('d.m.Y H:i') }} von {{ $version->creator?->name ?? '–' }}
                            </div>
                            @if($version->is_sensitive)
                                <x-elternabfragen.badge color="red" icon="fas fa-user-shield" class="mt-1.5">sensibel – Zusatzprüfung</x-elternabfragen.badge>
                            @endif
                        </div>
                        <a href="{{ route('elternabfragen.admin.templates.show', $version) }}#pruefung" class="ea-btn ea-btn-primary ea-btn-sm">Prüfen <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </li>
                @empty
                    <li><div class="ea-empty !py-8"><div class="ea-empty-icon"><i class="fas fa-check" aria-hidden="true"></i></div><p class="text-sm mb-0">Keine Vorlagen zur Prüfung.</p></div></li>
                @endforelse
            </ul>
        </section>

        <section class="ea-card">
            <div class="ea-card-header">
                <div class="ea-card-icon"><i class="fas fa-paper-plane" aria-hidden="true"></i></div>
                <div class="flex-1"><h2 class="ea-card-title">Abfragen vor dem Versand</h2><p class="ea-card-sub">Zielgruppe, Frist und Kanäle prüfen</p></div>
                <span class="ea-tab-count {{ $inquiries->count() ? '' : '!bg-gray-400' }}">{{ $inquiries->count() }}</span>
            </div>
            <ul class="ea-divide mb-0">
                @forelse($inquiries as $inquiry)
                    <li class="px-5 py-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}" class="ea-link">{{ $inquiry->title }}</a>
                            <div class="text-xs ea-muted mt-0.5">
                                {{ $inquiry->targetLabel() }} · Start {{ $inquiry->starts_at->format('d.m.Y H:i') }} · Frist {{ $inquiry->deadline_at->format('d.m.Y') }}
                                · von {{ $inquiry->creator?->name ?? '–' }}
                            </div>
                        </div>
                        <a href="{{ route('elternabfragen.admin.inquiries.show', $inquiry) }}#pruefung" class="ea-btn ea-btn-primary ea-btn-sm">Prüfen <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
                    </li>
                @empty
                    <li><div class="ea-empty !py-8"><div class="ea-empty-icon"><i class="fas fa-check" aria-hidden="true"></i></div><p class="text-sm mb-0">Keine Abfragen zur Freigabe.</p></div></li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
@endsection
