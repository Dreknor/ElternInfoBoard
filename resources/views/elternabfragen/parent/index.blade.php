@extends('layouts.app')

@section('title', '| Elternabfragen')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-3 mb-1">
        <i class="fas fa-clipboard-check text-blue-600" aria-hidden="true"></i> Elternabfragen
    </h1>
    <p class="text-sm text-gray-600 mb-6">Abfragen der Schule zu Ihren Kindern. Jede Abfrage gilt für ein Kind – bei mehreren Kindern bitte für jedes Kind ausfüllen.</p>

    <section aria-labelledby="offen" class="mb-8">
        <h2 id="offen" class="text-lg font-semibold text-gray-700 mb-3">Offen ({{ $open->count() }})</h2>
        @forelse($open as $assignment)
            @php $state = $states[$assignment->id]; @endphp
            <a href="{{ route('elternabfragen.show', $assignment) }}"
               class="block bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-3 hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">
                            <i class="fas fa-child" aria-hidden="true"></i> {{ $assignment->childName() }}
                        </p>
                        <p class="font-semibold text-gray-800 mt-1 break-words">{{ $assignment->inquiry->title }}</p>
                        <p class="text-sm text-gray-600 mt-1">
                            Bitte bis <strong>{{ $state['deadline']->format('d.m.Y, H:i') }} Uhr</strong>
                            @if($state['deadline']->isPast())
                                <span class="text-red-700">(Frist abgelaufen)</span>
                            @endif
                        </p>
                    </div>
                    <x-elternabfragen.badge :color="$assignment->displayColor()">{{ $assignment->displayLabel() }}</x-elternabfragen.badge>
                </div>
                @if($state['can_confirm'])
                    <p class="text-sm text-blue-800 mt-2"><i class="fas fa-user-check" aria-hidden="true"></i> Bereits beantwortet – bitte prüfen und bestätigen Sie die Angaben.</p>
                @endif
                @unless($state['can_view_answers'])
                    <p class="text-xs text-gray-500 mt-2"><i class="fas fa-info-circle" aria-hidden="true"></i> Nur zur Information – beantworten können sorgeberechtigte Personen.</p>
                @endunless
            </a>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 p-6 text-center text-gray-500">
                <i class="fas fa-check-circle text-green-400 text-3xl mb-2" aria-hidden="true"></i>
                <p>Zurzeit sind keine Abfragen offen.</p>
            </div>
        @endforelse
    </section>

    @if($done->isNotEmpty())
        <section aria-labelledby="erledigt">
            <h2 id="erledigt" class="text-lg font-semibold text-gray-700 mb-3">Erledigt und abgeschlossen</h2>
            <ul class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
                @foreach($done as $assignment)
                    <li>
                        <a href="{{ route('elternabfragen.show', $assignment) }}" class="flex items-center justify-between gap-3 p-4 hover:bg-gray-50">
                            <span class="min-w-0">
                                <span class="block font-medium text-gray-800 break-words">{{ $assignment->inquiry->title }}</span>
                                <span class="block text-sm text-gray-500">
                                    {{ $assignment->childName() }}
                                    @if($assignment->currentResponse)
                                        · eingereicht {{ $assignment->currentResponse->submitted_at->format('d.m.Y H:i') }}
                                    @endif
                                </span>
                            </span>
                            <x-elternabfragen.badge :color="$assignment->displayColor()">{{ $assignment->displayLabel() }}</x-elternabfragen.badge>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
